<?php

/*
 * Ikon garis 24×24 (stroke 2, currentColor) untuk helper icon().
 * Digambar khusus untuk proyek ini — tanpa icon font/library (§9.5).
 */

declare(strict_types=1);

return [
    'home'          => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
    'grid'          => '<rect x="4" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="4" width="6.5" height="6.5" rx="1.5"/><rect x="4" y="13.5" width="6.5" height="6.5" rx="1.5"/><rect x="13.5" y="13.5" width="6.5" height="6.5" rx="1.5"/>',
    'heart'         => '<path d="M12 20s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6C19.5 15.4 12 20 12 20z"/>',
    'newspaper'     => '<path d="M4 5h12v14H6a2 2 0 0 1-2-2z"/><path d="M16 9h4v8a2 2 0 0 1-2 2"/><path d="M8 9h4M8 13h4M8 16h2"/>',
    'menu'          => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    'search'        => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
    'x'             => '<path d="M6 6l12 12M18 6 6 18"/>',
    'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
    'chevron-left'  => '<path d="m15 6-6 6 6 6"/>',
    'chevron-down'  => '<path d="m6 9 6 6 6-6"/>',
    'arrow-right'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
    'calculator'    => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 7h8M8 12h.01M12 12h.01M16 12h.01M8 16h.01M12 16h.01M16 16h.01"/>',
    'upload'        => '<path d="M12 15V4M7 9l5-5 5 5"/><path d="M4 15v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/>',
    'bank'          => '<path d="M3 9.5 12 4l9 5.5z"/><path d="M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20.5h18"/>',
    'book'          => '<path d="M4 5a2 2 0 0 1 2-2h13v15H6a2 2 0 0 0-2 2z"/><path d="M4 20a2 2 0 0 0 2 1h13v-3"/><path d="M9 7h6"/>',
    'scale'         => '<path d="M12 4v16M8 20h8M5 7h14"/><path d="M5 7 2.5 13a2.5 2.5 0 0 0 5 0z"/><path d="M19 7l-2.5 6a2.5 2.5 0 0 0 5 0z"/>',
    'chart'         => '<path d="M4 4v16h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
    'video'         => '<rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10.5 5-3v9l-5-3"/>',
    'image'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.5"/><path d="m3 17 5-5 4 4 3-3 6 6"/>',
    'info'          => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    'users'         => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.7a3.5 3.5 0 0 1 0 6.6M18 14a6 6 0 0 1 3.5 6"/>',
    'star'          => '<path d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
    'star-solid'    => '<path fill="currentColor" stroke="none" d="m12 3.5 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.9l-5.2 2.7 1-5.8-4.3-4.1 5.9-.9z"/>',
    'phone'         => '<path d="M5 4h3.5l2 5-2.3 1.4a11 11 0 0 0 5.4 5.4L15 13.5l5 2V19a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1z"/>',
    'mail'          => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
    'map-pin'       => '<path d="M12 21s-7-6.1-7-11.2a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="10" r="2.5"/>',
    'clock'         => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    'calendar'      => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>',
    'document'      => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 12h6M9 16h6"/>',
    'download'      => '<path d="M12 4v11M7 10l5 5 5-5"/><path d="M4 19h16"/>',
    'play'          => '<path fill="currentColor" stroke="none" d="M8 5.5v13a.8.8 0 0 0 1.2.7l10.4-6.5a.8.8 0 0 0 0-1.4L9.2 4.8A.8.8 0 0 0 8 5.5z"/>',
    'check-circle'  => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'alert'         => '<path d="M12 4 2.5 20h19z"/><path d="M12 10v4M12 17h.01"/>',
    'copy'          => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h3"/>',
    'hand-heart'    => '<path d="M11 14h2a2 2 0 0 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 16"/><path d="m7 20 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.4a2 2 0 0 0-2.8-2.9l-4.2 3.9"/><path d="m2 15 6 6"/><path d="M19.5 8.5c.7-.7 1.5-1.6 1.5-2.7A2.4 2.4 0 0 0 16.5 5c-.6-.6-1.3-1-2.1-1A2.4 2.4 0 0 0 12 6.3c0 1.2.8 2 1.5 2.7L16.5 12z"/>',
    'graduation'    => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5M22 9v6"/>',
    'stethoscope'   => '<path d="M5 3v6a5 5 0 0 0 10 0V3"/><path d="M10 14v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="12" r="2"/>',
    'mosque'        => '<path d="M12 3c-3 2.5-5 4.4-5 7h10c0-2.6-2-4.5-5-7z"/><path d="M5 21V12M19 21V12M3 21h18M9 21v-4a3 3 0 0 1 6 0v4"/><path d="M5 9V6M19 9V6"/>',
    'store'         => '<path d="M4 9.5 5.5 4h13L20 9.5"/><path d="M4 9.5a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0 2.7 2.7 0 0 0 5.3 0"/><path d="M5 12v8h14v-8M10 20v-5h4v5"/>',
    'whatsapp'      => '<path d="M4 20l1.3-4A8 8 0 1 1 8.2 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 .8a3.5 3.5 0 0 1-1.8-1.8l.8-1-1-2z"/>',
    'instagram'     => '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17 7h.01"/>',
    'facebook'      => '<path d="M14 21v-7.5h3l.5-3.5H14V8.2c0-1 .4-1.7 1.8-1.7h1.8V3.3A20 20 0 0 0 15 3c-2.7 0-4.5 1.6-4.5 4.6V10H7.5v3.5h3V21"/>',
    'youtube'       => '<rect x="2.5" y="5" width="19" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
    'tiktok'        => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3a5 5 0 0 0 5 5"/>',
    'twitter'       => '<path d="M4 4l16 16M20 4 4 20"/>',
];
