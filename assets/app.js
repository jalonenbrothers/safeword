// SafeWord — progressive enhancement only. Core flow works without JS.
// (No-JS contract: copy button, print button, reveal-3rd-question toggle.)
(function () {
  'use strict';

  // reveal hidden copy buttons (copy-invite)
  document.querySelectorAll('button[data-copy]').forEach(function (btn) {
    btn.hidden = false;
    btn.addEventListener('click', function () {
      var el = document.querySelector(btn.getAttribute('data-copy'));
      if (!el) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(el.value).then(function () {
          btn.textContent = 'Copied';
          setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
        });
      } else {
        el.select();
        document.execCommand('copy');
        btn.textContent = 'Copied';
      }
    });
  });

  // print button
  var pb = document.getElementById('print-btn');
  if (pb) {
    pb.hidden = false;
    pb.addEventListener('click', function () { window.print(); });
  }

  // reveal 3rd challenge question
  var add = document.getElementById('add-third');
  if (add) {
    var target = document.querySelector(add.getAttribute('data-reveal'));
    if (target && target.classList.contains('hidden-optional')) {
      add.hidden = false;
      add.addEventListener('click', function () {
        target.classList.remove('hidden-optional');
        target.hidden = false;
        add.hidden = true;
      });
      // hide the optional third pair until toggled
      target.hidden = true;
    }
  }
})();
