{if isset($glb_url) && $glb_url}
<div class="wbglbviewer-block">
  <model-viewer
      src="{$glb_url|escape:'html':'UTF-8'}"
      crossorigin="anonymous"
      ar
      ar-modes="webxr scene-viewer quick-look"
      camera-controls
      autoplay
      shadow-intensity="1"
      environment-image="neutral"
      exposure="1"
      loading="eager"
      reveal="auto"
      style="width:100%;max-width:100%;height:480px;border-radius:12px;overflow:hidden;">
    <div slot="poster" style="display:flex;align-items:center;justify-content:center;height:100%;">
      <span>Loading 3D viewer…</span>
    </div>
  </model-viewer>
</div>
{/if}
