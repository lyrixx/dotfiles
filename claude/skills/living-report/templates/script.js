  (function () {
    var page = document.querySelector('.page');
    var prefix = page.getAttribute('data-storage-key') + ':';
    var boxes = document.querySelectorAll('input[type="checkbox"][data-group]');

    function read(key) {
      try { return window.localStorage.getItem(prefix + key); } catch (e) { return null; }
    }
    function write(key, value) {
      try { window.localStorage.setItem(prefix + key, value); } catch (e) { /* storage unavailable: the box still works for this visit */ }
    }
    function refreshCounts() {
      document.querySelectorAll('[data-count]').forEach(function (counter) {
        var group = counter.getAttribute('data-count');
        var all = document.querySelectorAll('input[data-group="' + group + '"]').length;
        var done = document.querySelectorAll('input[data-group="' + group + '"]:checked').length;
        counter.textContent = counter.getAttribute('data-format')
          .replace('{done}', done)
          .replace('{open}', all - done)
          .replace('{total}', all);
      });
    }

    boxes.forEach(function (box) {
      if (box.disabled) { return; }
      if ('1' === read(box.id)) { box.checked = true; }
      box.addEventListener('change', function () {
        write(box.id, box.checked ? '1' : '0');
        refreshCounts();
      });
    });
    refreshCounts();

    document.querySelectorAll('button[data-copy]').forEach(function (button) {
      var initialLabel = button.textContent;
      button.addEventListener('click', function () {
        var source = document.getElementById(button.getAttribute('data-copy'));
        var text = source.textContent;
        function selectSource() {
          var range = document.createRange();
          range.selectNodeContents(source);
          var selection = window.getSelection();
          selection.removeAllRanges();
          selection.addRange(range);
          button.textContent = 'Texte sélectionné, copie-le';
        }
        function restore() { setTimeout(function () { button.textContent = initialLabel; }, 1800); }
        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(function () {
            button.textContent = 'Copié';
            restore();
          }, function () {
            selectSource();
            restore();
          });
        } else {
          selectSource();
          restore();
        }
      });
    });
  })();
