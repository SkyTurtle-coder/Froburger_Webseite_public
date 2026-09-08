"""Browser contract test with real PHP markup/assets and a mocked AJAX response.

Run with Python + playwright installed, passing the PHP executable as first argument.
Uses installed Microsoft Edge; never accesses the live site or its participant data.
"""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from playwright.sync_api import sync_playwright, expect

root = Path(__file__).resolve().parent.parent
markup = subprocess.check_output([sys.argv[1], str(root / "tests/signup-pin-check.php"), "--render"], encoding="utf-8")
css = (root / "assets/css/events-lists.css").read_text(encoding="utf-8")
js = (root / "assets/js/events-lists.js").read_text(encoding="utf-8")
html = f'''<!doctype html><html lang="de"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>{css} body {{font-family: sans-serif; margin: 16px}} .avf-event-detail {{max-width: 640px; margin:auto}}</style>
<article class="avf-event-detail"><h1>Froburgfahrt</h1><div class="avf-event-detail__table-wrap"><table class="avf-event-detail__table"><tbody>
<tr><td>Newton</td><td>Ja</td><td data-signup-column="menu">Vegi</td><td data-signup-column="bus">Ja</td></tr>
</tbody></table></div>{markup}</article><script>{js}</script></html>'''

with sync_playwright() as p:
    browser = p.chromium.launch(channel="msedge", headless=True)
    for width in (1280, 390):
        context = browser.new_context(viewport={"width": width, "height": 900})
        page = context.new_page()
        errors = []
        calls = []
        page.on("pageerror", lambda error: errors.append(str(error)))
        pending = []

        def route_request(route):
            if route.request.url == "http://pin.test/":
                route.fulfill(content_type="text/html", body=html)
                return
            if route.request.url.endswith("admin-ajax.php"):
                body = route.request.post_data or ""
                calls.append(body)
                if '9999' in body:
                    route.fulfill(status=400, json={"success": False, "data": {"message": "Vulgo oder PIN ungültig."}})
                else:
                    saving = '\r\nupdate\r\n' in body
                    if not saving:
                        pending.append(route)
                        return
                    route.fulfill(json={"success": True, "data": {"signup": {
                        "vulgo": "Newton", "attending": not saving,
                        "values": {"menu": "Fleisch" if saving else "Vegi", "note": "Privat", "bus": not saving}
                    }}})
                return
            route.abort()

        page.route("**/*", route_request)
        page.goto("http://pin.test/")
        # Creation rejects malformed PINs in the existing custom form validation.
        page.locator("#avf-event-vulgo").fill("Test")
        page.locator("#avf-event-attending").select_option("1")
        page.locator("#avf-event-pin").fill("12ab")
        page.locator("[data-avf-event-form] [type=submit]").click()
        expect(page.locator("#avf-event-pin-error")).to_contain_text("4 bis 6")
        page.locator("summary").click()
        form = page.locator("[data-avf-edit-form]")
        expect(form.locator("fieldset")).to_be_hidden()
        form.locator("[name=vulgo]").fill("Newton")
        form.locator("[name=pin]").fill("9999")
        form.locator("[type=submit]").click()
        expect(form.locator("[data-avf-edit-message]")).to_contain_text("ungültig")
        expect(form.locator("fieldset")).to_be_hidden()
        form.locator("[name=pin]").fill("01234")
        form.locator("[type=submit]").click()
        expect(form.locator("[name=pin]")).to_have_attribute("readonly", "")
        expect(form.locator("[name=vulgo]")).to_have_attribute("readonly", "")
        page.wait_for_timeout(100)
        assert len(pending) == 1
        pending.pop().fulfill(json={"success": True, "data": {"signup": {
            "vulgo": "Newton", "attending": True,
            "values": {"menu": "Vegi", "note": "Privat", "bus": True}
        }}})
        expect(form.locator("fieldset")).to_be_visible()
        expect(form.locator('[name="values[menu]"]')).to_have_value("Vegi")
        expect(form.locator('[name="values[note]"]')).to_have_value("Privat")
        expect(form.locator('[name="values[bus]"]')).to_be_checked()
        expect(form.locator("[name=vulgo]")).to_have_attribute("readonly", "")
        page.screenshot(path=str(Path(tempfile.gettempdir()) / f"avf-pin-edit-{width}.png"), full_page=True)
        assert page.evaluate("document.documentElement.scrollWidth <= innerWidth"), "Horizontal overflow"
        form.locator("[name=attending]").select_option("0")
        form.locator('[name="values[menu]"]').select_option("Fleisch")
        form.locator('[name="values[bus]"]').uncheck()
        form.locator("[type=submit]").click()
        expect(form.locator("[data-avf-edit-message]")).to_contain_text("gespeichert")
        expect(form.locator("fieldset")).to_be_hidden()
        expect(form.locator("[name=pin]")).to_have_value("")
        expect(page.locator('[data-signup-column="menu"]')).to_have_text("Fleisch")
        expect(page.locator('[data-signup-column="bus"]')).to_have_text("Nein")
        assert len(calls) == 3
        assert '\r\n01234\r\n' in calls[-1], "Leading zero lost"
        assert not errors, errors
        context.close()
        print(f"Browser PIN workflow passed at {width}px")
    browser.close()
