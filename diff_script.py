import re, difflib, codecs

with codecs.open('public/index.php.bak', 'r', 'utf-8') as f:
    bak_text = f.read()

with codecs.open('public/index.php', 'r', 'utf-8') as f:
    curr_text = f.read()

bak = re.search(r'<div class="signature-section">.*?<div class="submit-container">', bak_text, re.DOTALL).group(0)
curr = re.search(r'<div class="signature-section">.*?<div class="submit-container">', curr_text, re.DOTALL).group(0)

diff = ''.join(difflib.unified_diff(bak.splitlines(True), curr.splitlines(True)))
with codecs.open('diff_html.txt', 'w', 'utf-8') as f:
    f.write(diff)
