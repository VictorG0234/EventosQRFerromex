import { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

export default function EditInvitation({ auth, event, settings, imageUrl, defaultImageUrl, qrPreviewUrl }) {
    const form = useForm({
        image: null,
        clear_image: false,
        email_subject: settings.email_subject,
        email_message: settings.email_message || '',
        qr_x: Number(settings.qr_x),
        qr_y: Number(settings.qr_y),
        qr_size: Number(settings.qr_size),
    });
    const imageRef = useRef(null);
    const dragRef = useRef(null);
    const [previewImageUrl, setPreviewImageUrl] = useState(imageUrl);
    const [notice, setNotice] = useState(null);

    const getImageAspectRatio = () => {
        const bounds = imageRef.current?.getBoundingClientRect();
        return bounds?.width && bounds?.height ? bounds.width / bounds.height : 714 / 1179;
    };

    const updateQrSetting = (name, value) => {
        const aspectRatio = getImageAspectRatio();
        const next = {
            qr_x: Number(form.data.qr_x),
            qr_y: Number(form.data.qr_y),
            qr_size: Number(form.data.qr_size),
        };
        const numericValue = Number(value);

        if (name === 'qr_size') {
            next.qr_size = Math.min(
                numericValue,
                80,
                100 - next.qr_x,
                (100 - next.qr_y) * aspectRatio
            );
        } else if (name === 'qr_x') {
            next.qr_x = Math.min(numericValue, 100 - next.qr_size);
        } else if (name === 'qr_y') {
            next.qr_y = Math.min(numericValue, 100 - (next.qr_size / aspectRatio));
        }

        form.setData({ ...form.data, ...next });
    };

    useEffect(() => {
        setPreviewImageUrl(imageUrl);
        form.setData('image', null);
        form.setData('clear_image', false);
    }, [imageUrl]);

    useEffect(() => {
        return () => {
            if (previewImageUrl.startsWith('blob:')) {
                URL.revokeObjectURL(previewImageUrl);
            }
        };
    }, [previewImageUrl]);

    const chooseImage = (file) => {
        if (!file) return;

        if (previewImageUrl.startsWith('blob:')) {
            URL.revokeObjectURL(previewImageUrl);
        }

        form.setData('image', file);
        form.setData('clear_image', false);
        setPreviewImageUrl(URL.createObjectURL(file));
    };

    const beginAdjustment = (event, mode) => {
        event.preventDefault();
        event.stopPropagation();
        event.currentTarget.setPointerCapture(event.pointerId);
        dragRef.current = {
            mode,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
            qrX: Number(form.data.qr_x),
            qrY: Number(form.data.qr_y),
            qrSize: Number(form.data.qr_size),
        };
    };

    const updateAdjustment = (event) => {
        const drag = dragRef.current;
        const image = imageRef.current;
        if (!drag || !image || drag.pointerId !== event.pointerId) return;

        const bounds = image.getBoundingClientRect();
        if (!bounds.width || !bounds.height) return;

        const aspectRatio = bounds.width / bounds.height;
        const deltaX = ((event.clientX - drag.startX) / bounds.width) * 100;
        const deltaY = ((event.clientY - drag.startY) / bounds.height) * 100;
        const next = { qr_x: drag.qrX, qr_y: drag.qrY, qr_size: drag.qrSize };

        if (drag.mode === 'resize') {
            const maxSize = Math.min(
                100,
                (100 - drag.qrX) / 1,
                ((100 - drag.qrY) * aspectRatio)
            );
            next.qr_size = Math.max(5, Math.min(maxSize, drag.qrSize + Math.max(deltaX, deltaY * aspectRatio)));
        } else {
            next.qr_x = Math.max(0, Math.min(100 - drag.qrSize, drag.qrX + deltaX));
            next.qr_y = Math.max(
                0,
                Math.min(100 - (drag.qrSize / aspectRatio), drag.qrY + deltaY)
            );
        }

        form.setData({ ...form.data, ...next });
    };

    const endAdjustment = (event) => {
        if (dragRef.current?.pointerId === event.pointerId) {
            dragRef.current = null;
        }
    };

    const resetImage = () => {
        if (previewImageUrl.startsWith('blob:')) {
            URL.revokeObjectURL(previewImageUrl);
        }

        form.setData('image', null);
        form.setData('clear_image', true);
        setPreviewImageUrl(defaultImageUrl);
    };

    const saveInvitation = (eventObject) => {
        eventObject.preventDefault();
        setNotice(null);
        form.post(route('events.invitation.update', event.id), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setNotice('La invitación se guardó correctamente.'),
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 className="text-xl font-semibold text-gray-800 dark:text-white">
                            Personalizar invitación
                        </h2>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-300">{event.name}</p>
                    </div>
                    <Link
                        href={route('events.emails.index', event.id)}
                        className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Volver a correos
                    </Link>
                </div>
            }
        >
            <Head title={`Personalizar invitación - ${event.name}`} />

            <div className="py-8">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {notice && (
                        <div role="status" className="rounded-md border border-green-200 bg-green-50 p-4 text-green-800">
                            {notice}
                        </div>
                    )}

                    <form onSubmit={saveInvitation} className="grid grid-cols-1 gap-6 lg:grid-cols-5">
                        <section className="space-y-6 rounded-lg bg-white p-6 shadow lg:col-span-2">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">Imagen y QR</h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    Sube una imagen base y arrastra el código QR a la posición deseada. Usa la esquina del QR para cambiar su tamaño.
                                </p>
                            </div>

                            <div>
                                <label htmlFor="invitation-image" className="mb-2 block text-sm font-medium text-gray-700">
                                    Imagen base
                                </label>
                                <input
                                    id="invitation-image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(eventObject) => chooseImage(eventObject.target.files?.[0])}
                                    className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p className="mt-1 text-xs text-gray-500">JPG, PNG o WebP, máximo 10 MB.</p>
                                {form.errors.image && <p className="mt-1 text-sm text-red-600">{form.errors.image}</p>}
                                {settings.image_path && (
                                    <button
                                        type="button"
                                        onClick={resetImage}
                                        className="mt-3 text-sm font-medium text-red-600 hover:text-red-800"
                                    >
                                        Restaurar imagen predeterminada
                                    </button>
                                )}
                            </div>

                            <div className="space-y-4">
                                {[
                                    {
                                        name: 'qr_x',
                                        label: 'Posición horizontal',
                                        max: Math.min(95, 100 - Number(form.data.qr_size)),
                                    },
                                    {
                                        name: 'qr_y',
                                        label: 'Posición vertical',
                                        max: Math.min(95, 100 - (Number(form.data.qr_size) / getImageAspectRatio())),
                                    },
                                    {
                                        name: 'qr_size',
                                        label: 'Tamaño del QR',
                                        max: Math.min(
                                            80,
                                            100 - Number(form.data.qr_x),
                                            (100 - Number(form.data.qr_y)) * getImageAspectRatio()
                                        ),
                                    },
                                ].map((control) => (
                                    <div key={control.name}>
                                        <div className="mb-1 flex justify-between text-sm">
                                            <label htmlFor={control.name} className="font-medium text-gray-700">{control.label}</label>
                                            <span className="text-gray-500">{Number(form.data[control.name]).toFixed(1)}%</span>
                                        </div>
                                        <input
                                            id={control.name}
                                            type="range"
                                            min={control.name === 'qr_size' ? 5 : 0}
                                            max={Math.max(control.max, Number(form.data[control.name]))}
                                            step="0.1"
                                            value={form.data[control.name]}
                                            onChange={(eventObject) => updateQrSetting(control.name, eventObject.target.value)}
                                            className="w-full accent-blue-600"
                                        />
                                    </div>
                                ))}
                                {['qr_x', 'qr_y', 'qr_size'].map((name) =>
                                    form.errors[name] ? <p key={name} className="text-sm text-red-600">{form.errors[name]}</p> : null
                                )}
                            </div>

                            <div className="border-t pt-5">
                                <h3 className="text-lg font-semibold text-gray-900">Correo de invitación</h3>
                                <label htmlFor="email_subject" className="mb-1 mt-4 block text-sm font-medium text-gray-700">
                                    Asunto
                                </label>
                                <input
                                    id="email_subject"
                                    type="text"
                                    value={form.data.email_subject}
                                    onChange={(eventObject) => form.setData('email_subject', eventObject.target.value)}
                                    maxLength={255}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                />
                                {form.errors.email_subject && <p className="mt-1 text-sm text-red-600">{form.errors.email_subject}</p>}

                                <label htmlFor="email_message" className="mb-1 mt-4 block text-sm font-medium text-gray-700">
                                    Texto opcional del correo
                                </label>
                                <textarea
                                    id="email_message"
                                    rows={5}
                                    maxLength={5000}
                                    value={form.data.email_message}
                                    onChange={(eventObject) => form.setData('email_message', eventObject.target.value)}
                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Escribe un mensaje que acompañará la imagen de invitación."
                                />
                                {form.errors.email_message && <p className="mt-1 text-sm text-red-600">{form.errors.email_message}</p>}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="w-full rounded-md bg-blue-700 px-4 py-3 font-semibold text-white hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                {form.processing ? 'Guardando…' : 'Guardar invitación'}
                            </button>
                        </section>

                        <section className="rounded-lg bg-white p-6 shadow lg:col-span-3">
                            <div className="mb-5">
                                <h3 className="text-lg font-semibold text-gray-900">Vista previa del correo</h3>
                                <p className="mt-1 text-sm text-gray-600">
                                    El QR se incrustará en la imagen adjunta y también aparecerá en el cuerpo del correo.
                                </p>
                            </div>

                            <div className="mx-auto max-w-xl rounded-md border bg-gray-50 p-4">
                                <p className="text-xs uppercase tracking-wide text-gray-500">Asunto</p>
                                <p className="mb-4 font-medium text-gray-900">{form.data.email_subject || 'Sin asunto'}</p>
                                {form.data.email_message && (
                                    <p className="mb-4 whitespace-pre-wrap break-words text-sm text-gray-700">
                                        {form.data.email_message}
                                    </p>
                                )}
                                <div className="overflow-auto rounded bg-white p-2 text-center">
                                    <div
                                        className="relative mx-auto inline-block max-w-full touch-none select-none"
                                        onPointerMove={updateAdjustment}
                                        onPointerUp={endAdjustment}
                                        onPointerCancel={endAdjustment}
                                    >
                                        <img
                                            ref={imageRef}
                                            src={previewImageUrl}
                                            alt="Imagen base de la invitación"
                                            draggable="false"
                                            className="block max-h-[75vh] max-w-full"
                                        />
                                        <div
                                            onPointerDown={(eventObject) => beginAdjustment(eventObject, 'move')}
                                            style={{
                                                left: `${form.data.qr_x}%`,
                                                top: `${form.data.qr_y}%`,
                                                width: `${form.data.qr_size}%`,
                                                touchAction: 'none',
                                            }}
                                            className="absolute cursor-move border-2 border-blue-600"
                                        >
                                            <img src={qrPreviewUrl} alt="Vista previa del código QR" draggable="false" className="block h-auto w-full" />
                                            <button
                                                type="button"
                                                aria-label="Cambiar tamaño del código QR"
                                                onPointerDown={(eventObject) => beginAdjustment(eventObject, 'resize')}
                                                className="absolute -bottom-2 -right-2 h-5 w-5 touch-none rounded-full border-2 border-white bg-blue-700 shadow"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <p className="mt-3 text-xs text-gray-500">
                                    Arrastra el QR para moverlo. El control azul de la esquina permite redimensionarlo.
                                </p>
                            </div>
                        </section>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
