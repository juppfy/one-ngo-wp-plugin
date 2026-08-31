(function () {
  var allowed = (window.oneNgoPublic && oneNgoPublic.embedOrigin) || "";

  function applyHeight(source, height) {
    var frames = document.querySelectorAll("iframe.one-ngo-page-frame");
    for (var i = 0; i < frames.length; i += 1) {
      if (frames[i].contentWindow === source && height > 80 && height < 20000) {
        frames[i].style.height = height + "px";
      }
    }
  }

  window.addEventListener("message", function (event) {
    if (allowed && event.origin !== allowed) {
      return;
    }
    if (!event.data || event.data.source !== "one-ngo" || event.data.type !== "embed-height") {
      return;
    }
    applyHeight(event.source, Number(event.data.height) || 0);
  });
})();
