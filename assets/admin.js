(function () {
  function copy(text, button) {
    if (!navigator.clipboard || !text) return;
    navigator.clipboard.writeText(text).then(function () {
      if (!button) return;
      var original = button.textContent;
      button.textContent = "Copied";
      setTimeout(function () {
        button.textContent = original;
      }, 1600);
    });
  }

  document.addEventListener("click", function (event) {
    var button = event.target.closest("[data-one-ngo-copy-btn]");
    if (!button) return;
    event.preventDefault();
    copy(button.getAttribute("data-one-ngo-copy-btn") || "", button);
  });
})();
