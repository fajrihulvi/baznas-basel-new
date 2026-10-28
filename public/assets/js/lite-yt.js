// Facade video YouTube: iframe youtube-nocookie baru dimuat saat thumbnail diklik.
// Tanpa JS, link membuka video di YouTube.

let sudahPreconnect = false;

function preconnect() {
  if (sudahPreconnect) return;
  sudahPreconnect = true;
  for (const asal of ['https://www.youtube-nocookie.com', 'https://i.ytimg.com']) {
    const l = document.createElement('link');
    l.rel = 'preconnect';
    l.href = asal;
    document.head.append(l);
  }
}

document.addEventListener('pointerover', (e) => {
  if (e.target instanceof Element && e.target.closest('a[data-yt]')) preconnect();
}, { passive: true });

document.addEventListener('click', (e) => {
  const link = e.target instanceof Element ? e.target.closest('a[data-yt]') : null;
  if (!link) return;

  const id = link.dataset.yt ?? '';
  if (!/^[\w-]{11}$/.test(id)) return;
  e.preventDefault();

  const iframe = document.createElement('iframe');
  iframe.src = `https://www.youtube-nocookie.com/embed/${id}?autoplay=1&rel=0`;
  iframe.title = link.dataset.ytTitle || 'Video YouTube';
  iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
  iframe.allowFullscreen = true;
  iframe.className = 'absolute inset-0 size-full';

  const wadah = document.createElement('div');
  wadah.className = 'relative aspect-video bg-ink';
  wadah.append(iframe);
  link.replaceWith(wadah);
  iframe.focus();
});
