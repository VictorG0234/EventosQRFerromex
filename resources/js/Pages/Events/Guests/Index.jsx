import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { 
    PlusIcon, 
    DocumentArrowUpIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    QrCodeIcon,
    PencilIcon,
    TrashIcon
} from '@heroicons/react/24/outline';
import { useState, useEffect } from 'react';
import { useDebouncedCallback } from 'use-debounce';

export default function Index({ auth, event, guests, filters }) {
    const [search, setSearch] = useState(filters?.search || '');
    const [filter, setFilter] = useState(filters?.filter || 'all');
    const [processing, setProcessing] = useState(false);

    // Debounce para la búsqueda (esperar 500ms después de que el usuario deje de escribir)
    const debouncedSearch = useDebouncedCallback((value) => {
        router.get(
            route('events.guests.index', event.id),
            { search: value, filter: filter },
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true
            }
        );
    }, 500);

    // Aplicar búsqueda con debounce
    useEffect(() => {
        debouncedSearch(search);
    }, [search]);

    // Aplicar filtro inmediatamente
    useEffect(() => {
        router.get(
            route('events.guests.index', event.id),
            { search: search, filter: filter },
            { 
                preserveState: true,
                preserveScroll: true,
                replace: true
            }
        );
    }, [filter]);

    const handleDelete = (guest) => {
        if (confirm(`¿Estás seguro de eliminar a ${guest.full_name}?`)) {
            setProcessing(true);
            router.delete(route('events.guests.destroy', [event.id, guest.id]), {
                onFinish: () => setProcessing(false)
            });
        }
    };
            });
        }
    };

    // Usar estadísticas del evento completo, no solo de la página actual
    const stats = {
        total: event.guests_count || 0,
        attended: event.guests_with_attendance || 0,
        not_attended: (event.guests_count || 0) - (event.guests_with_attendance || 0),
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-col gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-0">
                            <Link
                                href={route('events.show', event.id)}
                                className="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white text-sm sm:mr-4"
                            >
                                ← Volver
                            </Link>
                            <div>
                                <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-white leading-tight">
                                    Invitados - {event.name}
                                </h2>
                                <p className="text-xs sm:text-sm text-gray-600 dark:text-gray-300">
                                    {stats.attended} de {stats.total} confirmados ({event.attendances_count || 0} asistencias)
                                </p>
                            </div>
                        </div>
                        
                        <div className="flex items-center gap-2">
                            <Link
                                href={route('events.guests.import', event.id)}
                                className="inline-flex items-center px-2 sm:px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-xs sm:text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800"
                            >
                                <DocumentArrowUpIcon className="w-4 h-4 sm:mr-2" />
                                <span className="hidden sm:inline">Importar CSV</span>
                            </Link>
                            
                            <Link
                                href={route('events.guests.create', event.id)}
                                className="inline-flex items-center px-3 sm:px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                            >
                                <PlusIcon className="w-4 h-4 sm:mr-2" />
                                <span className="hidden sm:inline">Nuevo Invitado</span>
                            </Link>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Invitados - ${event.name}`} />

            <div className="py-6 sm:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    
                    {/* Estadísticas rápidas */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                        <div className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 overflow-hidden shadow-lg sm:rounded-lg">
                            <div className="p-4 sm:p-6 text-center">
                                <div className="text-2xl sm:text-3xl font-bold text-white">{stats.total}</div>
                                <div className="text-xs sm:text-sm text-blue-100 dark:text-blue-200 mt-1">Total Invitados</div>
                            </div>
                        </div>
                        
                        <div className="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 overflow-hidden shadow-lg sm:rounded-lg">
                            <div className="p-4 sm:p-6 text-center">
                                <div className="text-2xl sm:text-3xl font-bold text-white">{stats.attended}</div>
                                <div className="text-xs sm:text-sm text-green-100 dark:text-green-200 mt-1">Con Asistencia</div>
                            </div>
                        </div>
                        
                        <div className="bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 overflow-hidden shadow-lg sm:rounded-lg">
                            <div className="p-4 sm:p-6 text-center">
                                <div className="text-2xl sm:text-3xl font-bold text-white">{stats.not_attended}</div>
                                <div className="text-xs sm:text-sm text-orange-100 dark:text-orange-200 mt-1">Sin Asistencia</div>
                            </div>
                        </div>
                        
                        <div className="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 overflow-hidden shadow-lg sm:rounded-lg">
                            <div className="p-4 sm:p-6 text-center">
                                <div className="text-2xl sm:text-3xl font-bold text-white">
                                    {stats.total > 0 ? Math.round((stats.attended / stats.total) * 100) : 0}%
                                </div>
                                <div className="text-xs sm:text-sm text-purple-100 dark:text-purple-200 mt-1">Tasa de Asistencia</div>
                            </div>
                        </div>
                    </div>

                    {/* Filtros y búsqueda */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4 sm:mb-6">
                        <div className="p-4 sm:p-6">
                            <div className="flex flex-col gap-3 sm:flex-row sm:gap-4 sm:items-center sm:justify-between">
                                <div className="flex-1 w-full sm:max-w-lg">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 dark:text-gray-500" />
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Buscar por nombre, empleado, puesto..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            className="block w-full pl-9 sm:pl-10 pr-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-md leading-5 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:placeholder-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                </div>
                                
                                <div className="flex items-center">
                                    <div className="flex items-center w-full sm:w-auto">
                                        <FunnelIcon className="h-4 w-4 text-gray-400 dark:text-gray-500 mr-2" />
                                        <select
                                            value={filter}
                                            onChange={(e) => setFilter(e.target.value)}
                                            className="flex-1 sm:flex-none text-sm border border-gray-300 dark:border-gray-600 rounded-md shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="all">Todos</option>
                                            <option value="attended">Con asistencia</option>
                                            <option value="not_attended">Sin asistencia</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Lista de invitados */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        {guests?.data?.length === 0 ? (
                            <div className="p-8 sm:p-12 text-center">
                                <UserIcon className="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400" />
                                <h3 className="mt-3 sm:mt-4 text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {!search && filter === 'all'
                                        ? 'No hay invitados aún'
                                        : 'No se encontraron invitados'
                                    }
                                </h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    {!search && filter === 'all'
                                        ? 'Comienza importando invitados desde un archivo CSV'
                                        : 'Intenta ajustar los filtros de búsqueda'
                                    }
                                </p>
                                {!search && filter === 'all' && (
                                    <div className="mt-6">
                                        <Link
                                            href={route('events.guests.import', event.id)}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                        >
                                            <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                            Importar Invitados
                                        </Link>
                                    </div>
                                )}
                            </div>
                        ) : (
                            <>
                                {/* Header de la tabla - solo visible en desktop */}
                                <div className="hidden lg:block bg-gray-50 dark:bg-gray-700 px-6 py-3 border-b border-gray-200 dark:border-gray-600">
                                    <div className="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        <div className="col-span-1">Estado</div>
                                        <div className="col-span-3">Nombre Completo</div>
                                        <div className="col-span-2">Empleado</div>
                                        <div className="col-span-2">Puesto/Localidad</div>
                                        <div className="col-span-2">Asistencia</div>
                                        <div className="col-span-2 text-right">Acciones</div>
                                    </div>
                                </div>

                                {/* Filas de invitados */}
                                <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                    {guests?.data?.map((guest) => (
                                        <div key={guest.id} className="px-4 sm:px-6 py-3 sm:py-4 hover:bg-gray-50 dark:hover:bg-gray-700 group">
                                            {/* Vista móvil - formato tarjeta */}
                                            <div className="lg:hidden space-y-2">
                                                <div className="flex items-start justify-between">
                                                    <div className="flex items-start space-x-3">
                                                        {guest.has_attended ? (
                                                            <CheckCircleIcon className="h-5 w-5 text-green-500 dark:text-green-400 flex-shrink-0 mt-0.5" />
                                                        ) : (
                                                            <XCircleIcon className="h-5 w-5 text-gray-400 dark:text-gray-600 flex-shrink-0 mt-0.5" />
                                                        )}
                                                        <div className="flex-1 min-w-0">
                                                            <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                                {guest.full_name}
                                                            </div>
                                                            <div className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                {guest.numero_empleado} • {guest.puesto}
                                                            </div>
                                                            {guest.localidad && (
                                                                <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                    {guest.localidad}
                                                                </div>
                                                            )}
                                                            {guest.has_attended && guest.attended_at && (
                                                                <div className="text-xs text-green-600 dark:text-green-400 mt-1">
                                                                    ✓ {guest.attended_at}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex items-center justify-end space-x-1 pt-1">
                                                    <Link
                                                        href={route('events.guests.show', [event.id, guest.id])}
                                                        className="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 p-2"
                                                        title="Ver"
                                                    >
                                                        <UserIcon className="w-4 h-4" />
                                                    </Link>
                                                    <a
                                                        href={`/storage/${guest.qr_code_path}`}
                                                        download={`QR_${guest.numero_empleado}.png`}
                                                        className="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 p-2"
                                                        title="QR"
                                                    >
                                                        <QrCodeIcon className="w-4 h-4" />
                                                    </a>
                                                    <Link
                                                        href={route('events.guests.edit', [event.id, guest.id])}
                                                        className="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300 p-2"
                                                        title="Editar"
                                                    >
                                                        <PencilIcon className="w-4 h-4" />
                                                    </Link>
                                                    <button
                                                        onClick={() => handleDelete(guest)}
                                                        disabled={processing}
                                                        className="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 p-2 disabled:opacity-50"
                                                        title="Eliminar"
                                                    >
                                                        <TrashIcon className="w-4 h-4" />
                                                    </button>
                                                </div>
                                            </div>

                                            {/* Vista desktop - formato tabla */}
                                            <div className="hidden lg:grid grid-cols-12 gap-4 items-center">
                                                {/* Estado */}
                                                <div className="col-span-1">
                                                    {guest.has_attended ? (
                                                        <CheckCircleIcon className="h-6 w-6 text-green-500 dark:text-green-400" title="Asistió" />
                                                    ) : (
                                                        <XCircleIcon className="h-6 w-6 text-gray-400 dark:text-gray-600" title="No asistió" />
                                                    )}
                                                </div>

                                                {/* Nombre */}
                                                <div className="col-span-3">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {guest.full_name}
                                                    </div>
                                                    {guest.categoria_rifa && (
                                                        <div className="text-xs text-gray-500 dark:text-gray-400">
                                                            Rifa: {guest.categoria_rifa}
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Número de empleado */}
                                                <div className="col-span-2">
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {guest.numero_empleado}
                                                    </div>
                                                </div>

                                                {/* Puesto y Localidad */}
                                                <div className="col-span-2">
                                                    <div className="text-sm text-gray-500 dark:text-gray-400">
                                                        {guest.puesto}
                                                    </div>
                                                    <div className="text-xs text-gray-500 dark:text-gray-400">
                                                        {guest.localidad}
                                                    </div>
                                                </div>

                                                {/* Asistencia */}
                                                <div className="col-span-2">
                                                    {guest.has_attended ? (
                                                        <div>
                                                            <div className="text-sm text-green-600 dark:text-green-400 font-medium">
                                                                Registrada
                                                            </div>
                                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                {guest.attended_at}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-500 dark:text-gray-400">
                                                            Sin registrar
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Acciones */}
                                                <div className="col-span-2 text-right">
                                                    <div className="flex justify-end items-center space-x-2">
                                                        <Link
                                                            href={route('events.guests.show', [event.id, guest.id])}
                                                            className="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300 p-1"
                                                            title="Ver detalles"
                                                        >
                                                            <UserIcon className="w-4 h-4" />
                                                        </Link>
                                                        
                                                        <a
                                                            href={`/storage/${guest.qr_code_path}`}
                                                            download={`QR_${guest.numero_empleado}.png`}
                                                            className="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 p-1"
                                                            title="Descargar QR"
                                                        >
                                                            <QrCodeIcon className="w-4 h-4" />
                                                        </a>
                                                        
                                                        <Link
                                                            href={route('events.guests.edit', [event.id, guest.id])}
                                                            className="text-orange-600 dark:text-orange-400 hover:text-orange-900 dark:hover:text-orange-300 p-1"
                                                            title="Editar"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        
                                                        <button
                                                            onClick={() => handleDelete(guest)}
                                                            disabled={processing}
                                                            className="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 p-1 disabled:opacity-50"
                                                            title="Eliminar"
                                                        >
                                                            <TrashIcon className="w-4 h-4" />
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Paginación */}
                                {guests?.links && (
                                    <div className="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
                                        <div className="flex flex-col sm:flex-row items-center justify-between gap-3">
                                            <div className="text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                                                Mostrando {guests.from} a {guests.to} de {guests.total}
                                            </div>
                                            <div className="flex flex-wrap justify-center gap-1 sm:gap-2">
                                                {guests.links.map((link, index) => (
                                                    link.url ? (
                                                        <Link
                                                            key={index}
                                                            href={link.url}
                                                            className={`px-2 sm:px-3 py-1 sm:py-2 text-xs sm:text-sm border rounded ${
                                                                link.active 
                                                                    ? 'bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-900/30 dark:border-blue-500 dark:text-blue-400' 
                                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600'
                                                            }`}
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                        />
                                                    ) : (
                                                        <span
                                                            key={index}
                                                            className="px-2 sm:px-3 py-1 sm:py-2 text-xs sm:text-sm border rounded bg-gray-100 border-gray-300 text-gray-400 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-600"
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                        />
                                                    )
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}