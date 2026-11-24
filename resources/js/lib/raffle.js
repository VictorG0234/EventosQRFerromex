export const DEFAULT_RAFFLE_SETTINGS = {
    title: 'Sorteo de Fin de Año',
    primary_color: '#33529F',
    prize_color: '#B2D6EC',
    accent_color: '#ed2d36',
    background_url: '/images/raffle-border-background.png',
    logo_url: '/images/logo-gm.png',
    audio_url: '/audio/rifa.mp3',
    sound_enabled: true,
    confetti_enabled: true,
    confetti_style: 'snow',
    confetti_colors: ['#ed2d36'],
    confetti_duration: 6,
};

export function resolveRaffleSettings(settings) {
    return { ...DEFAULT_RAFFLE_SETTINGS, ...(settings || {}) };
}

// Variables CSS que consumen las clases .raffle-* de app.css
export function raffleStyleVars(settings) {
    const s = resolveRaffleSettings(settings);

    return {
        '--raffle-primary': s.primary_color,
        '--raffle-prize': s.prize_color,
        '--raffle-accent': s.accent_color,
        '--raffle-bg-image': `url("${s.background_url}")`,
    };
}

const random = (min, max) => Math.random() * (max - min) + min;

export function launchRaffleConfetti(settings) {
    const s = resolveRaffleSettings(settings);
    const confetti = window.confetti;

    if (!s.confetti_enabled || typeof confetti !== 'function') return;

    const colors = s.confetti_colors?.length ? s.confetti_colors : DEFAULT_RAFFLE_SETTINGS.confetti_colors;
    const duration = Number(s.confetti_duration) * 1000;
    const end = Date.now() + duration;

    if (s.confetti_style === 'burst') {
        (function frame() {
            confetti({ particleCount: 4, angle: 60, spread: 60, origin: { x: 0, y: 0.8 }, colors });
            confetti({ particleCount: 4, angle: 120, spread: 60, origin: { x: 1, y: 0.8 }, colors });
            if (Date.now() < end) requestAnimationFrame(frame);
        })();
        return;
    }

    if (s.confetti_style === 'fireworks') {
        const timer = setInterval(() => {
            if (Date.now() >= end) return clearInterval(timer);
            confetti({
                particleCount: 40,
                startVelocity: 30,
                spread: 360,
                ticks: 60,
                origin: { x: random(0.1, 0.9), y: random(0.1, 0.5) },
                colors,
            });
        }, 250);
        return;
    }

    let skew = 1;
    (function frame() {
        const timeLeft = end - Date.now();
        const ticks = Math.max(200, 500 * (timeLeft / duration));
        skew = Math.max(0.8, skew - 0.001);

        confetti({
            particleCount: 1,
            startVelocity: 0,
            ticks,
            origin: { x: Math.random(), y: Math.random() * skew },
            colors,
            shapes: ['circle'],
            gravity: random(0.4, 2),
            scalar: random(0.4, 2),
            drift: random(-0.4, 1),
        });

        if (timeLeft > 0) requestAnimationFrame(frame);
    })();
}
