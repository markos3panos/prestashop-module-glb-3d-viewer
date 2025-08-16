(function () {
  // Αν ο custom element υπάρχει ήδη, δεν κάνουμε τίποτα
  if (window.customElements && window.customElements.get('model-viewer')) return;

  // Φόρτωσε ESM build (για modern browsers)
  var s1 = document.createElement('script');
  s1.type = 'module';
  s1.src = 'https://unpkg.com/@google/model-viewer@3/dist/model-viewer.min.js';
  document.head.appendChild(s1);

  // Και legacy build για παλαιούς browsers
  var s2 = document.createElement('script');
  s2.setAttribute('nomodule', '');
  s2.src = 'https://unpkg.com/@google/model-viewer@3/dist/model-viewer-legacy.js';
  document.head.appendChild(s2);
})();
