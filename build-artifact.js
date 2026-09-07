/* Bundles the site into ONE self-contained .html file (all CSS, JS and images
   inlined) for previewing / sharing.   Run:  node build-artifact.js          */
const fs = require('fs');
const path = require('path');

const root = __dirname;
const read = f => fs.readFileSync(path.join(root, f), 'utf8');

/* --- image helpers ----------------------------------------------------- */
const svgUri = f =>
  'data:image/svg+xml,' +
  fs.readFileSync(path.join(root, f), 'utf8')
    .replace(/\r?\n\s*/g, ' ')
    .replace(/</g, '%3C').replace(/>/g, '%3E')
    .replace(/#/g, '%23')
    .replace(/"/g, '%22').replace(/'/g, '%27');

const pngUri = f =>
  'data:image/png;base64,' + fs.readFileSync(path.join(root, f)).toString('base64');

const IMG = {
  'img/doodles.svg': svgUri('img/doodles.svg'),
  'img/logo-dark.png': pngUri('img/logo-dark.png'),
  'img/logo-light.png': pngUri('img/logo-light.png')
};
for (let i = 1; i <= 6; i++) IMG['img/work-' + i + '.svg'] = svgUri('img/work-' + i + '.svg');

/* --- css --------------------------------------------------------------- */
let css = read('css/style.css');
css = css.replace(/url\(['"]?\.\.\/(img\/[a-z0-9.-]+)['"]?\)/gi,
  (m, f) => 'url("' + (IMG[f] || f) + '")');

/* --- html body --------------------------------------------------------- */
let html = read('index.html');
let body = html.slice(html.indexOf('<body>') + 6, html.indexOf('</body>'));

// strip the tags that only make sense in the standalone file
body = body.replace(/<script src="js\/main\.js[^"]*"><\/script>/, '');

// inline every image reference — data URIs must always be quoted
body = body.replace(/url\((["']?)(img\/[a-z0-9.-]+)\1\)/gi,
  (m, q, f) => IMG[f] ? 'url(\'' + IMG[f] + '\')' : m);
body = body.replace(/src="(img\/[a-z0-9.-]+)"/gi,
  (m, f) => IMG[f] ? 'src="' + IMG[f] + '"' : m);

const js = read('js/main.js');

/* --- compose ----------------------------------------------------------- */
const out = `<title>Ebitkar Smart Solutions</title>
<meta name="description" content="ابتكار للحلول الذكية — تصميم وبرمجة المواقع، تطبيقات الموبايل، الأنظمة المخصصة، والتسويق الرقمي.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;800&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<script>
  // the artifact host owns <html>, so set language + direction at runtime
  document.documentElement.setAttribute('lang','ar');
  document.documentElement.setAttribute('dir','rtl');
</script>
<style>
${css}
</style>
${body}
<script>
${js}
</script>
`;

fs.writeFileSync(path.join(root, 'dist-preview.html'), out);
console.log('dist-preview.html written —', (out.length / 1024).toFixed(0), 'KB');
