<?php
require_once __DIR__ . '/config.php';
header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache');
$settings = [];
try { foreach (database()->query('SELECT * FROM site_settings') as $row) $settings[$row['setting_key']] = json_decode($row['setting_value'], true); } catch (PDOException $e) { error_log($e->getMessage()); }
echo '(() => { const settings = ' . json_encode($settings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';';
?>
const apply = () => {
    const page = location.pathname.split('/').pop() || 'index.php';
    for (const change of settings.content || []) {
        if (change.page !== page && change.page !== '*') continue;
        try { document.querySelectorAll(change.selector).forEach(el => {
            if (el.closest('form') || ['SCRIPT','STYLE','INPUT','TEXTAREA'].includes(el.tagName)) return;
            if(change.type === 'text') el.textContent = change.value;
            else if(change.type === 'image' && el.tagName === 'IMG') el.src = change.value;
            else if(change.type === 'link' && el.tagName === 'A') el.href = change.value;
        }); } catch {}
    }
    if(settings.announcement) {
        let banner = document.getElementById('site-announcement');
        if(!banner) { banner = document.createElement('div'); banner.id = 'site-announcement'; banner.setAttribute('role','status'); banner.style.cssText='position:fixed;bottom:16px;left:16px;right:16px;z-index:900;background:#f0e8db;color:#191919;padding:16px 48px 16px 20px;border-radius:12px;box-shadow:0 4px 24px #0005'; document.body.append(banner); }
        banner.textContent = settings.announcement;
        const close = document.createElement('button');close.type='button';close.textContent='×';close.setAttribute('aria-label','Fechar aviso');close.style.cssText='position:absolute;right:14px;top:10px;border:0;background:none;font-size:24px;cursor:pointer';close.onclick=()=>banner.remove();banner.append(close);
    }
};
if(document.readyState === 'loading') document.addEventListener('DOMContentLoaded', apply); else apply();
})();
