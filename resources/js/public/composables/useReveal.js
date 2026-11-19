import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Adds a one-shot reveal animation when the element scrolls into view.
 * Falls back to "already visible" when IntersectionObserver is unavailable.
 */
export function useReveal(options = {}) {
    const target = ref(null);
    const visible = ref(false);
    let observer = null;

    onMounted(() => {
        if (!target.value || typeof IntersectionObserver === 'undefined') {
            visible.value = true;

            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        visible.value = true;
                        observer?.disconnect();
                    }
                });
            },
            { threshold: options.threshold ?? 0.12, rootMargin: options.rootMargin ?? '0px 0px -40px 0px' },
        );

        observer.observe(target.value);
    });

    onBeforeUnmount(() => observer?.disconnect());

    return { target, visible };
}
