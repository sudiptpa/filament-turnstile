// Stands in for https://challenges.cloudflare.com/turnstile/v0/api.js.
// It follows the parts of the real API the package uses, including the
// error the real widget throws when asked to reset or remove a widget whose
// container is no longer on the page.
(function () {
    const widgets = {};
    let nextId = 0;
    let nextToken = 0;

    const stats = { renders: 0, resets: 0, removes: 0, resetErrors: 0 };

    function issueToken(id) {
        const widget = widgets[id];

        if (!widget) {
            return;
        }

        nextToken += 1;
        setTimeout(() => widget.options.callback('stub-' + id + '-' + nextToken), 5);
    }

    window.turnstile = {
        render(container, options) {
            const id = 'w' + ++nextId;
            widgets[id] = { container, options };
            stats.renders += 1;
            issueToken(id);

            return id;
        },
        reset(id) {
            const widget = widgets[id];

            if (!widget || !widget.container.isConnected) {
                stats.resetErrors += 1;
                throw new Error('[Cloudflare Turnstile] Nothing to reset found for provided container.');
            }

            stats.resets += 1;
            issueToken(id);
        },
        remove(id) {
            if (widgets[id]) {
                stats.removes += 1;
                delete widgets[id];
            }
        },
    };

    window.__turnstileStub = {
        stats,
        liveWidgets: () => Object.keys(widgets).filter((id) => widgets[id].container.isConnected).length,
    };
})();
