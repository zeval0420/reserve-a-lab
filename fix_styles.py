import re

with open('/opt/lampp/htdocs/reserve-a-lab/reserve-a-lab/supervisor.php', 'r') as f:
    content = f.read()

# 1. Convert all rem to px in the <style> block
def convert_rem(match):
    val = float(match.group(1))
    px_val = round(val * 16)
    return f"{px_val}px"

# Extract style block
style_match = re.search(r'<style>(.*?)</style>', content, flags=re.DOTALL)
if style_match:
    style_content = style_match.group(1)
    
    # rename .modal to .custom-modal
    style_content = re.sub(r'\.modal\b', '.custom-modal', style_content)
    
    # replace rem with px
    style_content = re.sub(r'([\d\.]+)rem', convert_rem, style_content)
    
    content = content[:style_match.start(1)] + style_content + content[style_match.end(1):]

# 2. Rename <div class="modal" id="modal"> to custom-modal
content = content.replace('class="modal"', 'class="custom-modal"')
content = content.replace("getElementById('modal')", "getElementById('custom-modal')")

# 3. Check for space between navbar and header
# Is there a margin on .page or something?
# Let's remove `* { margin: 0; padding: 0; }` from supervisor.php CSS since it might strip header paddings.
# Wait, if we remove it, we should make sure .page elements don't get huge margins.
# Actually, the user said "space between navbar and header".
# Wait, `header.php` has:
# <header class="header background-gradient">
# <nav class="main-nav">
# Let's see if we can find any CSS rule in supervisor.php that might cause the space.
# In supervisor.php, `*, *::before, *::after` has `margin: 0; padding: 0;`. This might REMOVE negative margins from Bootstrap's row/container, causing gaps.
# Let's remove the global reset from supervisor.php to let Bootstrap do its thing.
content = re.sub(r'\*,\s*\*::before,\s*\*::after\s*\{\s*box-sizing:\s*border-box;\s*margin:\s*0;\s*padding:\s*0;\s*\}', '', content)

with open('/opt/lampp/htdocs/reserve-a-lab/reserve-a-lab/supervisor.php', 'w') as f:
    f.write(content)

