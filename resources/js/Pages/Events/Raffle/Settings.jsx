import { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { launchRaffleConfetti, raffleStyleVars } from '@/lib/raffle';

const PREVIEW_WIDTH = 1600;
const PREVIEW_HEIGHT = 900;
const MAX_CONFETTI_COLORS = 5;

const CONFETTI_STYLES = [
    { value: 'snow', label: 'Nieve (cae lentamente)' },
    { value: 'burst', label: 'Cañones laterales' },
    { value: 'fireworks', label: 'Fuegos artificiales' },
];

function FileField({ id, label, accept, hint, error, hasCustom, onChange, onClear, clearLabel }) {
    return (
        <div>
            <label htmlFor={id} className="mb-2 block text-sm font-medium text-gray-700">{label}</label>
            <input
                id={id}
                type="file"
                accept={accept}
                onChange={(e) => onChange(e.target.files?.[0] ?? null)}
                className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
            />
            <p className="mt-1 text-xs text-gray-500">{hint}</p>
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
            {hasCustom && (
                <button type="button" onClick={onClear} className="mt-2 text-sm font-medium text-red-600 hover:text-red-800">
                    {clearLabel}
                </button>
            )}
        </div>
    );
}

function ColorField({ label, value, onChange, error }) {
    return (
        <div>
            <label className="mb-1 block text-sm font-medium text-gray-700">{label}</label>
            <div className="flex items-center gap-2">
                <input
                    type="color"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="h-10 w-12 cursor-pointer rounded border border-gray-300 bg-white p-1"
                />
                <span className="font-mono text-sm text-gray-600">{value.toUpperCase()}</span>
            </div>
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

export default function RaffleSettings({ auth, event, settings }) {
    const form = useForm({
        title: settings.title,
        primary_color: settings.primary_color,
        prize_color: settings.prize_color,
        accent_color: settings.accent_color,
        sound_enabled: settings.sound_enabled,
        confetti_enabled: settings.confetti_enabled,
        confetti_style: settings.confetti_style,
        confetti_colors: settings.confetti_colors,
        confetti_duration: settings.confetti_duration,
        background: null,
        logo: null,
        audio: null,
        clear_background: false,
        clear_logo: false,
        clear_audio: false,
    });

    const [backgroundUrl, setBackgroundUrl] = useState(settings.background_url);
    const [logoUrl, setLogoUrl] = useState(settings.logo_url);
    const [audioUrl, setAudioUrl] = useState(settings.audio_url);
    const [notice, setNotice] = useState(null);
    const objectUrls = useRef([]);

    const previewBox = useRef(null);
    const [scale, setScale] = useState(0.5);

    useEffect(() => {
        const element = previewBox.current;
        if (!element) return undefined;

        const observer = new ResizeObserver(() => setScale(element.clientWidth / PREVIEW_WIDTH));
        observer.observe(element);
        setScale(element.clientWidth / PREVIEW_WIDTH);

        return () => observer.disconnect();
    }, []);

    useEffect(() => () => objectUrls.current.forEach((url) => URL.revokeObjectURL(url)), []);

    const chooseFile = (field, setUrl) => (file) => {
        form.setData((data) => ({ ...data, [field]: file, [`clear_${field}`]: false }));
        if (file) {
            const url = URL.createObjectURL(file);
            objectUrls.current.push(url);
            setUrl(url);
        }
    };

    const clearFile = (field, setUrl, defaultUrl) => () => {
        form.setData((data) => ({ ...data, [field]: null, [`clear_${field}`]: true }));
        setUrl(defaultUrl);
    };

    const updateConfettiColor = (index, value) => {
        form.setData('confetti_colors', form.data.confetti_colors.map((color, i) => (i === index ? value : color)));
    };

    const addConfettiColor = () => {
        if (form.data.confetti_colors.length < MAX_CONFETTI_COLORS) {
            form.setData('confetti_colors', [...form.data.confetti_colors, '#ffffff']);
        }
    };

    const removeConfettiColor = (index) => {
        if (form.data.confetti_colors.length > 1) {
            form.setData('confetti_colors', form.data.confetti_colors.filter((_, i) => i !== index));
        }
    };

    const appearance = {
        ...form.data,
        background_url: backgroundUrl,
        logo_url: logoUrl,
        audio_url: audioUrl,
    };

    const save = (e) => {
        e.preventDefault();
        setNotice(null);
        form.post(route('events.raffle.settings.update', event.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.setData((data) => ({ ...data, background: null, logo: null, audio: null, clear_background: false, clear_logo: false, clear_audio: false }));
                setNotice('La personalización de la rifa se guardó correctamente.');
            },
        });
    };

    const errorFor = (name) => form.errors[name];
    const confettiError = Object.keys(form.errors).find((key) => key.startsWith('confetti_colors'));

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-white">Personalizar rifa</h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{event.name}</p>
                    </div>
                    <Link
                        href={route('events.raffle.index', event.id)}
                        className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Volver a rifas
                    </Link>
                </div>
            }
        >
            <Head title={`Personalizar rifa - ${event.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {notice && (
                        <div role="status" className="rounded-md border border-green-200 bg-green-50 p-4 text-green-800">
                            {notice}
                        </div>
                    )}

                    <form onSubmit={save} className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                        <section className="space-y-6 rounded-lg bg-white p-6 shadow lg:col-span-2">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Apariencia</h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Lo que configures aquí se usa en la pantalla de rifa pública de este evento.
                                </p>
                            </div>

                            <div>
                                <label htmlFor="raffle-title" className="mb-1 block text-sm font-medium text-gray-700">Título</label>
                                <input
                                    id="raffle-title"
                                    type="text"
                                    maxLength={80}
                                    value={form.data.title}
                                    onChange={(e) => form.setData('title', e.target.value)}
                                    className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {errorFor('title') && <p className="mt-1 text-sm text-red-600">{errorFor('title')}</p>}
                            </div>

                            <FileField
                                id="raffle-background"
                                label="Imagen de fondo"
                                accept="image/jpeg,image/png,image/webp"
                                hint="JPG, PNG o WebP, máximo 10 MB. Se ajusta a pantalla completa (se recomienda 16:9)."
                                error={errorFor('background')}
                                hasCustom={Boolean(settings.background_path) || form.data.background !== null}
                                onChange={chooseFile('background', setBackgroundUrl)}
                                onClear={clearFile('background', setBackgroundUrl, settings.default_background_url)}
                                clearLabel="Restaurar fondo predeterminado"
                            />

                            <FileField
                                id="raffle-logo"
                                label="Logo del cliente"
                                accept="image/jpeg,image/png,image/webp"
                                hint="JPG, PNG o WebP, máximo 5 MB. Se recomienda PNG con fondo transparente."
                                error={errorFor('logo')}
                                hasCustom={Boolean(settings.logo_path) || form.data.logo !== null}
                                onChange={chooseFile('logo', setLogoUrl)}
                                onClear={clearFile('logo', setLogoUrl, settings.default_logo_url)}
                                clearLabel="Restaurar logo predeterminado"
                            />

                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <ColorField label="Color principal" value={form.data.primary_color} onChange={(v) => form.setData('primary_color', v)} error={errorFor('primary_color')} />
                                <ColorField label="Color del premio" value={form.data.prize_color} onChange={(v) => form.setData('prize_color', v)} error={errorFor('prize_color')} />
                                <ColorField label="Color de acento" value={form.data.accent_color} onChange={(v) => form.setData('accent_color', v)} error={errorFor('accent_color')} />
                            </div>

                            <hr />

                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Sonido</h3>
                                <label className="mt-2 flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={form.data.sound_enabled}
                                        onChange={(e) => form.setData('sound_enabled', e.target.checked)}
                                        className="rounded border-gray-300 text-blue-600"
                                    />
                                    Reproducir sonido durante la rifa
                                </label>
                            </div>

                            <FileField
                                id="raffle-audio"
                                label="Audio personalizado"
                                accept="audio/mpeg,audio/wav,audio/ogg,.mp3,.wav,.ogg"
                                hint="MP3, WAV u OGG, máximo 10 MB."
                                error={errorFor('audio')}
                                hasCustom={Boolean(settings.audio_path) || form.data.audio !== null}
                                onChange={chooseFile('audio', setAudioUrl)}
                                onClear={clearFile('audio', setAudioUrl, settings.default_audio_url)}
                                clearLabel="Restaurar audio predeterminado"
                            />
                            <audio key={audioUrl} controls src={audioUrl} className="w-full" />

                            <hr />

                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900">Confeti</h3>

                                <label className="flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="checkbox"
                                        checked={form.data.confetti_enabled}
                                        onChange={(e) => form.setData('confetti_enabled', e.target.checked)}
                                        className="rounded border-gray-300 text-blue-600"
                                    />
                                    Lanzar confeti al mostrar al ganador
                                </label>

                                <div>
                                    <label htmlFor="confetti-style" className="mb-1 block text-sm font-medium text-gray-700">Estilo</label>
                                    <select
                                        id="confetti-style"
                                        value={form.data.confetti_style}
                                        onChange={(e) => form.setData('confetti_style', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        {CONFETTI_STYLES.map((style) => (
                                            <option key={style.value} value={style.value}>{style.label}</option>
                                        ))}
                                    </select>
                                    {errorFor('confetti_style') && <p className="mt-1 text-sm text-red-600">{errorFor('confetti_style')}</p>}
                                </div>

                                <div>
                                    <label htmlFor="confetti-duration" className="mb-1 block text-sm font-medium text-gray-700">
                                        Duración: {form.data.confetti_duration} s
                                    </label>
                                    <input
                                        id="confetti-duration"
                                        type="range"
                                        min={2}
                                        max={20}
                                        value={form.data.confetti_duration}
                                        onChange={(e) => form.setData('confetti_duration', Number(e.target.value))}
                                        className="w-full"
                                    />
                                    {errorFor('confetti_duration') && <p className="mt-1 text-sm text-red-600">{errorFor('confetti_duration')}</p>}
                                </div>

                                <div>
                                    <span className="mb-1 block text-sm font-medium text-gray-700">Colores</span>
                                    <div className="flex flex-wrap items-center gap-3">
                                        {form.data.confetti_colors.map((color, index) => (
                                            <div key={index} className="flex items-center gap-1">
                                                <input
                                                    type="color"
                                                    value={color}
                                                    onChange={(e) => updateConfettiColor(index, e.target.value)}
                                                    className="h-10 w-12 cursor-pointer rounded border border-gray-300 bg-white p-1"
                                                />
                                                {form.data.confetti_colors.length > 1 && (
                                                    <button type="button" onClick={() => removeConfettiColor(index)} className="text-gray-400 hover:text-red-600" aria-label="Quitar color">✕</button>
                                                )}
                                            </div>
                                        ))}
                                        {form.data.confetti_colors.length < MAX_CONFETTI_COLORS && (
                                            <button type="button" onClick={addConfettiColor} className="rounded-md border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                                                + Agregar
                                            </button>
                                        )}
                                    </div>
                                    {confettiError && <p className="mt-1 text-sm text-red-600">{form.errors[confettiError]}</p>}
                                </div>

                                <button
                                    type="button"
                                    onClick={() => launchRaffleConfetti({ ...appearance, confetti_enabled: true })}
                                    className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    🎉 Probar confeti
                                </button>
                            </div>

                            <div className="flex justify-end">
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="rounded-md bg-blue-700 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-800 disabled:opacity-50"
                                >
                                    {form.processing ? 'Guardando...' : 'Guardar personalización'}
                                </button>
                            </div>
                        </section>

                        <section className="rounded-lg bg-white p-6 shadow lg:col-span-3">
                            <h3 className="text-lg font-semibold text-gray-900">Vista previa</h3>
                            <p className="mb-4 mt-1 text-sm text-gray-600">Así se verá la pantalla cuando haya un ganador.</p>

                            <div
                                ref={previewBox}
                                className="relative w-full overflow-hidden rounded-md border border-gray-200"
                                style={{ aspectRatio: `${PREVIEW_WIDTH} / ${PREVIEW_HEIGHT}` }}
                            >
                                <div
                                    style={{
                                        width: PREVIEW_WIDTH,
                                        height: PREVIEW_HEIGHT,
                                        transform: `scale(${scale})`,
                                        transformOrigin: 'top left',
                                    }}
                                >
                                    <div className="raffle-modal" style={raffleStyleVars(appearance)}>
                                        <div className="raffle-modal-inner">
                                            <div className="raffle-title">
                                                <img src={logoUrl} alt="Logo" className="raffle-logo" />
                                            </div>
                                            <p className="raffle-subtitle">{form.data.title}</p>
                                            <div className="raffle-employee-number-box">
                                                <p>CLIENTE</p>
                                                <p>000123</p>
                                            </div>
                                            <div className="raffle-prize-box">
                                                <p className="raffle-prize-text">PREMIO</p>
                                            </div>
                                            <div className="mb-6">
                                                <p className="raffle-winner-text">¡Ganador!</p>
                                                <p className="raffle-winner-name">Nombre del Ganador</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
