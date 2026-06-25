/**
 * AUTOSAV — TagInput v2
 * Remplace les textareas par des chips cliquables.
 * Bouton "+" pour ajouter (évite le conflit avec submit form).
 * Recherche AJAX avec autocomplete sur les valeurs existantes.
 *
 * Usage :
 *   new TagInput({
 *     wrap    : '#pre-wrap-q',    // container chips
 *     input   : '#pre-inp-q',     // champ texte
 *     btn     : '#pre-btn-q',     // bouton +
 *     hidden  : '#pre-hid-q',     // input hidden JSON
 *     suggest : '#pre-sug-q',     // dropdown suggestions
 *     searchUrl: '/ajax/qualifications/search', // AJAX URL
 *     csrf    : 'TOKEN',
 *     initial : []                // valeurs initiales
 *   });
 */
;(function(window) {
'use strict';

function TagInput(opts) {
  var self      = this;
  self.tags     = Array.isArray(opts.initial) ? opts.initial.slice() : [];
  self.$wrap    = document.querySelector(opts.wrap);
  self.$input   = document.querySelector(opts.input);
  self.$btn     = document.querySelector(opts.btn);
  self.$hidden  = document.querySelector(opts.hidden);
  self.$suggest = opts.suggest ? document.querySelector(opts.suggest) : null;
  self.searchUrl= opts.searchUrl || null;
  self.csrf     = opts.csrf || '';
  self.debounce = null;

  if (!self.$wrap || !self.$input || !self.$hidden) return;

  // ── Rendu des chips ───────────────────────────────────────────
  self.render = function() {
    // Supprimer les chips existants
    self.$wrap.querySelectorAll('.tag-chip').forEach(function(el) { el.remove(); });
    self.tags.forEach(function(t, i) {
      var chip = document.createElement('span');
      chip.className = 'tag-chip';
      chip.innerHTML = escHtml(t) + '<span class="rm" data-i="' + i + '" title="Supprimer">×</span>';
      chip.querySelector('.rm').addEventListener('click', function() {
        self.tags.splice(parseInt(this.dataset.i), 1);
        self.render();
      });
      self.$wrap.insertBefore(chip, self.$wrap.firstChild);
    });
    self.$hidden.value = JSON.stringify(self.tags);
  };

  // ── Ajouter un tag ────────────────────────────────────────────
  self.addTag = function(val) {
    val = val.trim();
    if (val && self.tags.indexOf(val) === -1) {
      self.tags.push(val);
      self.render();
    }
    self.$input.value = '';
    if (self.$suggest) hideSuggest();
  };

  // ── Bouton + ─────────────────────────────────────────────────
  if (self.$btn) {
    self.$btn.addEventListener('click', function(e) {
      e.preventDefault();
      self.addTag(self.$input.value);
      self.$input.focus();
    });
  }

  // ── Clic sur le container → focus input ──────────────────────
  self.$wrap.addEventListener('click', function() { self.$input.focus(); });

  // ── Touche Enter → ajouter (pas de submit) ───────────────────
  self.$input.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      e.stopPropagation();
      self.addTag(this.value);
      return false;
    }
    if (e.key === 'Backspace' && !this.value && self.tags.length) {
      self.tags.pop();
      self.render();
    }
    if (e.key === 'Escape' && self.$suggest) hideSuggest();
    if (e.key === 'ArrowDown' && self.$suggest) focusSuggestItem(0);
  });

  // ── Recherche AJAX autocomplete ───────────────────────────────
  if (self.searchUrl && self.$suggest) {
    self.$input.addEventListener('input', function() {
      var q = this.value.trim();
      clearTimeout(self.debounce);
      if (q.length < 2) { hideSuggest(); return; }
      self.debounce = setTimeout(function() {
        fetch(self.searchUrl + '?q=' + encodeURIComponent(q), {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': self.csrf
          }
        })
        .then(function(r) { return r.json(); })
        .then(function(json) {
          if (!json.success) return;
          var items = json.data.results || json.data.qualifications || json.data.skills || [];
          showSuggest(items);
        })
        .catch(function() {});
      }, 280);
    });

    self.$input.addEventListener('blur', function() {
      setTimeout(hideSuggest, 200);
    });
  }

  function showSuggest(items) {
    if (!items.length) { hideSuggest(); return; }
    self.$suggest.innerHTML = '';
    items.forEach(function(item) {
      var li = document.createElement('li');
      li.className = 'suggest-item';
      li.innerHTML = '<strong>' + escHtml(item.label) + '</strong>'
        + (item.code ? ' <small class="text-muted">(' + escHtml(item.code) + ')</small>' : '')
        + (item.pole ? ' <small class="text-muted">— ' + escHtml(item.pole) + '</small>' : '');
      li.addEventListener('mousedown', function(e) {
        e.preventDefault();
        self.addTag(item.label);
      });
      li.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); self.addTag(item.label); }
        if (e.key === 'ArrowDown') focusSuggestItem(Array.from(self.$suggest.children).indexOf(this) + 1);
        if (e.key === 'ArrowUp')  focusSuggestItem(Array.from(self.$suggest.children).indexOf(this) - 1);
      });
      self.$suggest.appendChild(li);
    });
    self.$suggest.style.display = 'block';
  }

  function hideSuggest() {
    if (self.$suggest) self.$suggest.style.display = 'none';
  }

  function focusSuggestItem(idx) {
    var items = self.$suggest.querySelectorAll('.suggest-item');
    if (items[idx]) { items[idx].focus(); }
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
  }

  // Rendu initial
  self.render();
}

window.TagInput = TagInput;

})(window);