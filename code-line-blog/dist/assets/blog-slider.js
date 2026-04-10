(function () {
    function getGap(track) {
        var styles = window.getComputedStyle(track);
        var gap = parseFloat(styles.columnGap || styles.gap || '0');
        return Number.isNaN(gap) ? 0 : gap;
    }

    function getStep(track) {
        var firstCard = track.querySelector('.clb-card');
        if (!firstCard) {
            return 0;
        }

        var cardWidth = firstCard.getBoundingClientRect().width;
        return cardWidth + getGap(track);
    }

    function updateButtons(track, prevButton, nextButton) {
        var maxScroll = track.scrollWidth - track.clientWidth;
        prevButton.disabled = track.scrollLeft <= 2;
        nextButton.disabled = track.scrollLeft >= (maxScroll - 2);
    }

    function bindSlider(root) {
        var track = root.querySelector('.clb-slider-track');
        var prevButton = root.querySelector('.clb-slider-prev');
        var nextButton = root.querySelector('.clb-slider-next');

        if (!track || !prevButton || !nextButton) {
            return;
        }

        prevButton.addEventListener('click', function () {
            track.scrollBy({ left: -getStep(track), behavior: 'smooth' });
        });

        nextButton.addEventListener('click', function () {
            track.scrollBy({ left: getStep(track), behavior: 'smooth' });
        });

        track.addEventListener('scroll', function () {
            updateButtons(track, prevButton, nextButton);
        }, { passive: true });

        window.addEventListener('resize', function () {
            updateButtons(track, prevButton, nextButton);
        });

        updateButtons(track, prevButton, nextButton);
    }

    document.addEventListener('DOMContentLoaded', function () {
        var sliders = document.querySelectorAll('[data-clb-slider="1"]');
        sliders.forEach(bindSlider);
    });
})();
