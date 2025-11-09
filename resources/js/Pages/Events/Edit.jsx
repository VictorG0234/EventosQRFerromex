import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarIcon, MapPinIcon, ClockIcon } from '@heroicons/react/24/outline';

export default function Edit({ auth, event }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: event.name || '',
        description: event.description || '',
        date: event.date || '',
        time: event.time || '',
        location: event.location || '',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('events.update', event.id));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.show', event.id)}
                        className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        ← Volver al evento
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Editar Evento
                    </h2>
                </div>
            }
        >
            <Head title={`Editar: ${event.name}`} />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            {/* Información del evento */}
                            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h3 className="font-semibold text-blue-900 mb-1">Editando evento</h3>
                                <p className="text-sm text-blue-700">
                                    {event.name} - Creado el {event.created_at || 'N/A'}
                                </p>
                            </div>

                            <form onSubmit={submit} className="space-y-6">
                                {/* Nombre del evento */}
                                <div>
                                    <InputLabel htmlFor="name" value="Nombre del Evento *" />
                                    <div className="mt-1 relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <CalendarIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <TextInput
                                            id="name"
                                            type="text"
                                            name="name"
                                            value={data.name}
                                            className="pl-10 block w-full"
                                            isFocused={true}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Ej: Evento Corporativo 2025"
                                        />
                                    </div>
                                    <InputError message={errors.name} className="mt-2" />
                                </div>

                                {/* Descripción */}
                                <div>
                                    <InputLabel htmlFor="description" value="Descripción" />
                                    <textarea
                                        id="description"
                                        name="description"
                                        rows={3}
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Descripción opcional del evento..."
                                    />
                                    <InputError message={errors.description} className="mt-2" />
                                </div>

                                {/* Fecha y Hora */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel htmlFor="date" value="Fecha *" />
                                        <div className="mt-1 relative">
                                            <TextInput
                                                id="date"
                                                type="date"
                                                name="date"
                                                value={data.date}
                                                className="block w-full"
                                                onChange={(e) => setData('date', e.target.value)}
                                            />
                                        </div>
                                        <InputError message={errors.date} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="time" value="Hora *" />
                                        <div className="mt-1 relative">
                                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                <ClockIcon className="h-5 w-5 text-gray-400" />
                                            </div>
                                            <TextInput
                                                id="time"
                                                type="time"
                                                name="time"
                                                value={data.time}
                                                className="pl-10 block w-full"
                                                onChange={(e) => setData('time', e.target.value)}
                                            />
                                        </div>
                                        <InputError message={errors.time} className="mt-2" />
                                    </div>
                                </div>

                                {/* Ubicación */}
                                <div>
                                    <InputLabel htmlFor="location" value="Ubicación *" />
                                    <div className="mt-1 relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MapPinIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <TextInput
                                            id="location"
                                            type="text"
                                            name="location"
                                            value={data.location}
                                            className="pl-10 block w-full"
                                            onChange={(e) => setData('location', e.target.value)}
                                            placeholder="Ej: Auditorio Principal, Ciudad de México"
                                        />
                                    </div>
                                    <InputError message={errors.location} className="mt-2" />
                                </div>

                                {/* Estado del evento */}
                                <div className="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <h4 className="font-medium text-gray-900">Estado del evento</h4>
                                            <p className="text-sm text-gray-600 mt-1">
                                                Actualmente: <span className={`font-semibold ${event.is_active ? 'text-green-600' : 'text-gray-600'}`}>
                                                    {event.is_active ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </p>
                                        </div>
                                        <Link
                                            href={route('events.toggle-active', event.id)}
                                            method="patch"
                                            as="button"
                                            className="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                                        >
                                            Cambiar estado
                                        </Link>
                                    </div>
                                </div>

                                {/* Nota informativa */}
                                <div className="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <p className="text-sm text-yellow-800">
                                        <strong>Nota:</strong> Los cambios en la fecha, hora o ubicación no afectarán los códigos QR ya generados para los invitados.
                                    </p>
                                </div>

                                {/* Botones */}
                                <div className="flex items-center justify-between pt-6 border-t">
                                    <Link
                                        href={route('events.show', event.id)}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Cancelar
                                    </Link>

                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Actualizando...' : 'Actualizar Evento'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
