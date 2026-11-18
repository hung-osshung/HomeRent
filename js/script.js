(function () {
    var modal = document.getElementById("authModal");
    var frame = document.getElementById("authFrame");

    window.openAuth = function (page) {
        if (!modal || !frame) {
            window.location.href = page;
            return;
        }
        frame.src = page;
        modal.hidden = false;
        document.body.classList.add("no-scroll");
    };

    window.closeAuth = function () {
        if (!modal || !frame) { return; }
        modal.hidden = true;
        frame.src = "about:blank";
        document.body.classList.remove("no-scroll");
    };

    document.addEventListener("click", function (event) {
        var trigger = event.target.closest("[data-auth]");
        if (trigger) {
            event.preventDefault();
            window.openAuth(trigger.getAttribute("data-auth"));
            return;
        }
        if (event.target.closest("[data-auth-close]") || event.target === modal) {
            window.closeAuth();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && modal && !modal.hidden) {
            window.closeAuth();
        }
    });

    var toggle = document.querySelector(".nav-toggle");
    var nav = document.getElementById("siteNav");
    if (toggle && nav) {
        toggle.addEventListener("click", function () {
            var open = nav.classList.toggle("is-open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }
})();
