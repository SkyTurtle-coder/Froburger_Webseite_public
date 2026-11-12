"""Read-only live-page regression check with local CSS/JS substituted in Chromium.
Usage: python card-title-browser.py
Requires Playwright with Chromium. No WordPress data is written.
"""
import json
import tempfile
from pathlib import Path
from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
SELECTOR = ".avf-post-recommendations__card-title:visible"
OUT = Path(tempfile.gettempdir()) / "avf-card-title-review"
OUT.mkdir(exist_ok=True)


def check(page):
    result = page.locator(SELECTOR).evaluate_all("""es => es.map(e => {
        const s = getComputedStyle(e);
        const r = document.createRange(); r.selectNodeContents(e);
        const content = r.getBoundingClientRect(), box = e.getBoundingClientRect();
        return {title:e.textContent, size:s.fontSize, inline:e.style.fontSize,
            overflow:e.scrollWidth > e.clientWidth + 1,
            clipped:content.bottom > box.bottom + 2};
    })""")
    assert result, "No visible recommendation titles"
    assert {r["size"] for r in result} == {"20px"}, result
    assert all(not r["inline"] and not r["overflow"] and not r["clipped"] for r in result), result
    assert page.evaluate("document.documentElement.scrollWidth <= innerWidth + 1"), "Page overflow"
    return len(result)


with sync_playwright() as pw:
    browser = pw.chromium.launch()
    context = browser.new_context()
    for kind in ("css", "js"):
        path = ROOT / "assets" / kind / ("post-template-frontend." + kind)
        context.route("**/avf-post-templates/assets/" + kind + "/post-template-frontend." + kind + "*",
            lambda route, request, path=path, kind=kind: route.fulfill(path=str(path), content_type="text/css" if kind == "css" else "application/javascript"))
    page = context.new_page()
    errors = []
    page.on("pageerror", lambda error: errors.append(str(error)))
    report = []
    for width in (1440, 1024, 768, 390, 320):
        page.set_viewport_size({"width":width,"height":1000})
        page.goto("https://www.avfroburger.ch/aktuelles/", wait_until="networkidle")
        page.evaluate("document.fonts.ready")
        initial = check(page)
        detail_url = page.locator(".avf-post-recommendations__card-link").first.get_attribute("href")
        button = page.locator("[data-avf-post-archive-button]")
        while button.is_visible():
            button.click()
            page.wait_for_timeout(300)
            check(page)
        page.mouse.move(0, 0)
        page.wait_for_timeout(350)
        count = check(page)
        # Metadata footers align within each grid row even for longer titles.
        footers = page.locator(".avf-post-recommendations__card:visible").evaluate_all("""es=>es.map(e=>({top:Math.round(e.getBoundingClientRect().top),bottom:e.querySelector('.avf-post-recommendations__meta').getBoundingClientRect().bottom}))""")
        for top in {f["top"] for f in footers}:
            values = [f["bottom"] for f in footers if f["top"] == top]
            assert max(values)-min(values) < 2, values
        page.screenshot(path=str(OUT / ("archive-" + str(width) + ".png")),full_page=True)
        report.append({"width":width,"initial":initial,"all":count})
    page.set_viewport_size({"width":1280,"height":900})
    page.goto(detail_url,wait_until="networkidle")
    check(page)
    # Simulate increased default text size: rem-based titles scale consistently.
    page.add_style_tag(content="html {font-size:32px !important;}")
    sizes=page.locator(SELECTOR).evaluate_all("es=>es.map(e=>getComputedStyle(e).fontSize)")
    assert set(sizes)=={"40px"},sizes
    assert not errors,errors
    browser.close()
    print(json.dumps({"archive":report,"detail":"passed","text_scaling":"passed","screenshots":str(OUT)},indent=2))
