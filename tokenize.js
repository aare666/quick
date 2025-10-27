// tokenize.js
(function() {
  function generateToken(length = 64) {
    const chars = 'abcdef0123456789';
    let token = '';
    for (let i = 0; i < length; i++) {
      token += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return token;
  }

  const token = generateToken();

  // Append token to all internal .html links
  document.querySelectorAll('a[href]').forEach(link => {
    const href = link.getAttribute('href');
    if (
      href.endsWith('.html') &&
      !href.startsWith('http') &&
      !href.includes('session_token=')
    ) {
      const separator = href.includes('?') ? '&' : '?';
      link.setAttribute('href', href + separator + 'session_token=' + token);
    }
  });
})();
