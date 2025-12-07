import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { 
    PlusIcon, 
    CalendarIcon, 
    MapPinIcon, 
    UsersIcon,
    EyeIcon,
    PencilIcon,
    TrashIcon,
    QrCodeIcon
} from '@heroicons/react/24/outline';
import { useState } from 'react';

export default function Index({ auth, events }) {
    const [processing, setProcessing] = useState(false);

    const handleDelete = (event) => {
        if (confirm(`¿Estás seguro de eliminar el evento "${event.name}"?`)) {
            setProcessing(true);
            router.delete(route('events.destroy', event.id), {
                onFinish: () => setProcessing(false)
            });
        }
    };

    const toggleActive = (event) => {
        setProcessing(true);
        router.patch(route('events.toggle-active', event.id), {}, {
            onFinish: () => setProcessing(false)
        });
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Mis Eventos
                    </h2>
                    <Link
                        href={route('events.create')}
                        className="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-offset-2 transition ease-in-out duration-150"
                        style={{ backgroundColor: '#D22730' }}
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Nuevo Evento
                    </Link>
                </div>
            }
        >
            <Head title="Mis Eventos" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {events && events.length === 0 ? (
                        // Estado vacío
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-12 text-center">
                                <CalendarIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-4 text-lg font-medium text-gray-900">
                                    No tienes eventos aún
                                </h3>
                                <p className="mt-2 text-gray-600">
                                    Comienza creando tu primer evento corporativo
                                </p>
                                <div className="mt-6">
                                    <Link
                                        href={route('events.create')}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <PlusIcon className="w-4 h-4 mr-2" />
                                        Crear Primer Evento
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ) : (
                        // Lista de eventos
                        <div className="grid gap-6 sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-3">
                            {events && events.map((event) => (
                                <div key={event.id} className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                                    <div className="p-6">
                                        {/* Header del evento */}
                                        <div className="flex items-start justify-between mb-4">
                                            <div className="flex-1">
                                                <h3 className="text-lg font-semibold text-gray-900 mb-1">
                                                    {event.name}
                                                </h3>
                                                <div className="flex items-center text-sm text-gray-600 mb-2">
                                                    <CalendarIcon className="w-4 h-4 mr-1" />
                                                    {event.event_date}
                                                </div>
                                                <div className="flex items-center text-sm text-gray-600">
                                                    <MapPinIcon className="w-4 h-4 mr-1" />
                                                    {event.location}
                                                </div>
                                            </div>
                                            <div className="flex flex-col items-end">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                                                    event.is_active 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-gray-100 text-gray-800'
                                                }`}>
                                                    {event.is_active ? 'Activo' : 'Inactivo'}
                                                </span>
                                            </div>
                                        </div>

                                        {/* Descripción */}
                                        {event.description && (
                                            <p className="text-sm text-gray-600 mb-4 line-clamp-2">
                                                {event.description}
                                            </p>
                                        )}

                                        {/* Estadísticas */}
                                        <div className="grid grid-cols-2 gap-4 mb-4">
                                            <div className="text-center p-3 bg-gray-50 rounded-lg">
                                                <div className="text-2xl font-bold text-blue-600">
                                                    {event.guests_count || 0}
                                                </div>
                                                <div className="text-xs text-gray-600">Invitados</div>
                                            </div>
                                            <div className="text-center p-3 bg-gray-50 rounded-lg">
                                                <div className="text-2xl font-bold text-green-600">
                                                    {event.attendance_rate || 0}%
                                                </div>
                                                <div className="text-xs text-gray-600">Asistencia</div>
                                            </div>
                                        </div>

                                        {/* Progress bar de asistencia */}
                                        <div className="w-full bg-gray-200 rounded-full h-2 mb-4">
                                            <div 
                                                className="bg-green-600 h-2 rounded-full transition-all duration-300"
                                                style={{ width: `${event.attendance_rate || 0}%` }}
                                            ></div>
                                        </div>

                                        {/* Acciones */}
                                        <div className="flex items-center justify-between pt-4 border-t border-gray-200">
                                            <div className="flex space-x-2">
                                                <Link
                                                    href={route('events.show', event.id)}
                                                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                >
                                                    <EyeIcon className="w-3 h-3 mr-1" />
                                                    Ver
                                                </Link>
                                                
                                                <Link
                                                    href={route('events.edit', event.id)}
                                                    className="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                                >
                                                    <PencilIcon className="w-3 h-3 mr-1" />
                                                    Editar
                                                </Link>

                                                <Link
                                                    href={route('events.guests.index', event.id)}
                                                    className="inline-flex items-center px-3 py-1.5 border border-blue-300 shadow-sm text-xs font-medium rounded text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                >
                                                    <UsersIcon className="w-3 h-3 mr-1" />
                                                    Invitados
                                                </Link>

                                                {event.guests_count > 0 && (
                                                    <Link
                                                        href={route('events.attendance.scanner', event.id)}
                                                        className="inline-flex items-center px-3 py-1.5 border border-green-300 shadow-sm text-xs font-medium rounded text-green-700 bg-green-50 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                    >
                                                        <QrCodeIcon className="w-3 h-3 mr-1" />
                                                        Scanner
                                                    </Link>
                                                )}
                                            </div>

                                            <button
                                                onClick={() => handleDelete(event)}
                                                disabled={processing}
                                                className="inline-flex items-center px-2 py-1.5 text-red-600 hover:text-red-900 disabled:opacity-50"
                                            >
                                                <TrashIcon className="w-3 h-3" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}