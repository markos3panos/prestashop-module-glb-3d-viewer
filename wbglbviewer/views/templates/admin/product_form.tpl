<div class="form-group">
  <label for="wbglb_file">{l s='3D Model (.glb)' mod='wbglbviewer'}</label>

  {if $glb_path}
    <div style="margin-bottom:6px;">
      <strong>{l s='Current file' mod='wbglbviewer'}:</strong>
      <a href="{$module_dir}{$glb_path|escape:'html':'UTF-8'}" target="_blank" rel="noopener">
        {$glb_path|escape:'html':'UTF-8'}
      </a>
      &nbsp;
      <button type="button" id="wbglb-delete" class="btn btn-xs btn-danger" style="vertical-align:baseline;">
        <i class="icon-trash"></i> {l s='Delete' mod='wbglbviewer'}
      </button>
    </div>
  {/if}

  <input type="file"
         id="wbglb_file"
         name="wbglb_file"
         accept=".glb"
         class="form-control"
         data-product-id="{$id_product|intval}"
         data-ajax-url="{$ajax_url|escape:'html':'UTF-8'}" />

  <input type="hidden" id="wbglb_token" value="{$ajax_token|escape:'html':'UTF-8'}" />

  <small class="text-muted" id="wbglb-status"></small>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const input   = document.getElementById('wbglb_file');
  const status  = document.getElementById('wbglb-status');
  const delBtn  = document.getElementById('wbglb-delete');
  const tokenEl = document.getElementById('wbglb_token');

  if (!input) return;

  const productId = input.dataset.productId;
  const baseUrl   = input.getAttribute('data-ajax-url'); // AdminModules&configure=wbglbviewer
  const token     = tokenEl ? tokenEl.value : '';

  function withToken(url) {
    const u = new URL(url, window.location.origin);
    if (!u.searchParams.has('token')) {
      u.searchParams.set('token', token);
    }
    return u.toString();
  }

  async function fetchJson(u, opts) {
    const r = await fetch(u, opts);
    const txt = await r.text();
    if (!r.ok) throw new Error('HTTP ' + r.status + ' — ' + txt.slice(0, 200));
    try { return JSON.parse(txt); } catch(e) { throw new Error('Bad JSON: ' + txt.slice(0, 200)); }
  }

  let timeout;

  input.addEventListener('change', function () {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      if (!input.files || !input.files.length) {
        status.textContent = '{l s='Please choose a .glb file.' mod='wbglbviewer'}';
        return;
      }
      status.textContent = '{l s='Uploading…' mod='wbglbviewer'}';

      const data = new FormData();
      data.append('ajax', '1');
      data.append('action', 'upload');
      data.append('id_product', productId);
      data.append('wbglb_file', input.files[0]);

      fetchJson(withToken(baseUrl), { method: 'POST', body: data, credentials: 'same-origin' })
        .then(json => {
          status.textContent = json.success
            ? '{l s='Saved.' mod='wbglbviewer'}'
            : '{l s='Error' mod='wbglbviewer'}: ' + (json.error || 'unknown');
          if (json.success) { window.location.reload(); }
        })
        .catch(e => { status.textContent = '{l s='AJAX error' mod='wbglbviewer'}: ' + (e.message || 'unknown'); });
    }, 400);
  });

  if (delBtn) {
    delBtn.addEventListener('click', function () {
      status.textContent = '{l s='Deleting…' mod='wbglbviewer'}';

      const params = new URLSearchParams();
      params.append('ajax', '1');
      params.append('action', 'delete');
      params.append('id_product', productId);

      fetchJson(withToken(baseUrl), {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin'
      })
      .then(json => {
        status.textContent = json.success
          ? '{l s='Deleted.' mod='wbglbviewer'}'
          : '{l s='Error' mod='wbglbviewer'}: ' + (json.error || 'unknown');
        if (json.success) { window.location.reload(); }
      })
      .catch(e => { status.textContent = '{l s='AJAX error' mod='wbglbviewer'}: ' + (e.message || 'unknown'); });
    });
  }
});
</script>
