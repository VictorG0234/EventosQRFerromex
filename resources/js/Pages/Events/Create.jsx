import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { CalendarIcon, MapPinIcon, ClockIcon } from '@heroicons/react/24/outline';

export default function Create({ auth }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        description: '',
        date: '',
        time: '',
        location: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('events.store'));
    };

    // Obtener fecha mínima (hoy)
    const today = new Date().toISOString().split('T')[0];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.index')}
                        className="mr-4 text-gray-600 hover:text-gray-900"
                    >
                        ← Volver a eventos
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Crear Nuevo Evento
                    </h2>
                </div>
            }
        >
            <Head title="Crear Evento" />

            <div className="py-12">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
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
                                                min={today}
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

                                {/* Botones */}
                                <div className="flex items-center justify-end space-x-3 pt-6 border-t">
                                    <Link
                                        href={route('events.index')}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Cancelar
                                    </Link>

                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Creando...' : 'Crear Evento'}
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