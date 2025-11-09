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
import { useState } from 'react';

export default function Index({ auth, event, guests }) {
    const [search, setSearch] = useState('');
    const [filter, setFilter] = useState('all'); // all, attended, not_attended
    const [processing, setProcessing] = useState(false);

    // Filtrar invitados
    const filteredGuests = guests?.data?.filter(guest => {
        const matchesSearch = !search || 
            guest.full_name.toLowerCase().includes(search.toLowerCase()) ||
            guest.numero_empleado.toLowerCase().includes(search.toLowerCase()) ||
            guest.area_laboral.toLowerCase().includes(search.toLowerCase());
        
        const matchesFilter = filter === 'all' || 
            (filter === 'attended' && guest.has_attended) ||
            (filter === 'not_attended' && !guest.has_attended);
            
        return matchesSearch && matchesFilter;
    }) || [];

    const handleDelete = (guest) => {
        if (confirm(`¿Estás seguro de eliminar a ${guest.full_name}?`)) {
            setProcessing(true);
            router.delete(route('events.guests.destroy', [event.id, guest.id]), {
                onFinish: () => setProcessing(false)
            });
        }
    };

    const stats = {
        total: guests?.data?.length || 0,
        attended: guests?.data?.filter(g => g.has_attended).length || 0,
        not_attended: guests?.data?.filter(g => !g.has_attended).length || 0,
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link
                            href={route('events.show', event.id)}
                            className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            ← Volver al evento
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Invitados - {event.name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                {stats.attended} de {stats.total} confirmados ({event.attendances_count || 0} asistencias)
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('events.guests.import', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800"
                        >
                            <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                            Importar CSV
                        </Link>
                        
                        <Link
                            href={route('events.guests.create', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
                        >
                            <PlusIcon className="w-4 h-4 mr-2" />
                            Nuevo Invitado
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Invitados - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Estadísticas rápidas */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 text-center">
                                <div className="text-2xl font-bold text-blue-600">{stats.total}</div>
                                <div className="text-sm text-gray-600">Total Invitados</div>
                            </div>
                        </div>
                        
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 text-center">
                                <div className="text-2xl font-bold text-green-600">{stats.attended}</div>
                                <div className="text-sm text-gray-600">Con Asistencia</div>
                            </div>
                        </div>
                        
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 text-center">
                                <div className="text-2xl font-bold text-yellow-600">{stats.not_attended}</div>
                                <div className="text-sm text-gray-600">Sin Asistencia</div>
                            </div>
                        </div>
                        
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 text-center">
                                <div className="text-2xl font-bold text-purple-600">
                                    {stats.total > 0 ? Math.round((stats.attended / stats.total) * 100) : 0}%
                                </div>
                                <div className="text-sm text-gray-600">Tasa de Asistencia</div>
                            </div>
                        </div>
                    </div>

                    {/* Filtros y búsqueda */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                                <div className="flex-1 max-w-lg">
                                    <div className="relative">
                                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
                                        </div>
                                        <input
                                            type="text"
                                            placeholder="Buscar por nombre, empleado o área..."
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
                                        />
                                    </div>
                                </div>
                                
                                <div className="flex items-center space-x-3">
                                    <div className="flex items-center">
                                        <FunnelIcon className="h-4 w-4 text-gray-400 mr-2" />
                                        <select
                                            value={filter}
                                            onChange={(e) => setFilter(e.target.value)}
                                            className="border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                        >
                                            <option value="all">Todos los invitados</option>
                                            <option value="attended">Con asistencia</option>
                                            <option value="not_attended">Sin asistencia</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Lista de invitados */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        {filteredGuests.length === 0 ? (
                            <div className="p-12 text-center">
                                <UserIcon className="mx-auto h-12 w-12 text-gray-400" />
                                <h3 className="mt-4 text-lg font-medium text-gray-900">
                                    {guests?.data?.length === 0 
                                        ? 'No hay invitados aún'
                                        : 'No se encontraron invitados'
                                    }
                                </h3>
                                <p className="mt-2 text-gray-600">
                                    {guests?.data?.length === 0 
                                        ? 'Comienza importando invitados desde un archivo CSV'
                                        : 'Intenta ajustar los filtros de búsqueda'
                                    }
                                </p>
                                {guests?.data?.length === 0 && (
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
                                {/* Header de la tabla */}
                                <div className="bg-gray-50 px-6 py-3 border-b border-gray-200">
                                    <div className="grid grid-cols-12 gap-4 text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <div className="col-span-1">Estado</div>
                                        <div className="col-span-3">Nombre Completo</div>
                                        <div className="col-span-2">Empleado</div>
                                        <div className="col-span-2">Área</div>
                                        <div className="col-span-2">Asistencia</div>
                                        <div className="col-span-2 text-right">Acciones</div>
                                    </div>
                                </div>

                                {/* Filas de invitados */}
                                <div className="divide-y divide-gray-200">
                                    {filteredGuests.map((guest) => (
                                        <div key={guest.id} className="px-6 py-4 hover:bg-gray-50">
                                            <div className="grid grid-cols-12 gap-4 items-center">
                                                {/* Estado */}
                                                <div className="col-span-1">
                                                    {guest.has_attended ? (
                                                        <CheckCircleIcon className="h-5 w-5 text-green-500" title="Asistió" />
                                                    ) : (
                                                        <XCircleIcon className="h-5 w-5 text-gray-400" title="No asistió" />
                                                    )}
                                                </div>

                                                {/* Nombre */}
                                                <div className="col-span-3">
                                                    <div className="text-sm font-medium text-gray-900">
                                                        {guest.full_name}
                                                    </div>
                                                    {guest.premios_rifa && Array.isArray(guest.premios_rifa) && guest.premios_rifa.length > 0 && (
                                                        <div className="text-xs text-gray-500">
                                                            Rifas: {guest.premios_rifa.join(', ')}
                                                        </div>
                                                    )}
                                                </div>

                                                {/* Número de empleado */}
                                                <div className="col-span-2">
                                                    <div className="text-sm text-gray-900">
                                                        {guest.numero_empleado}
                                                    </div>
                                                </div>

                                                {/* Área */}
                                                <div className="col-span-2">
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {guest.area_laboral}
                                                    </span>
                                                </div>

                                                {/* Asistencia */}
                                                <div className="col-span-2">
                                                    {guest.has_attended ? (
                                                        <div>
                                                            <div className="text-sm text-green-600 font-medium">
                                                                Registrada
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                {guest.attended_at}
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <span className="text-sm text-gray-500">
                                                            Sin registrar
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Acciones */}
                                                <div className="col-span-2 text-right">
                                                    <div className="flex justify-end items-center space-x-2">
                                                        <Link
                                                            href={route('events.guests.show', [event.id, guest.id])}
                                                            className="text-indigo-600 hover:text-indigo-900 p-1"
                                                            title="Ver detalles"
                                                        >
                                                            <UserIcon className="w-4 h-4" />
                                                        </Link>
                                                        
                                                        <a
                                                            href={`/storage/${guest.qr_code_path}`}
                                                            download={`QR_${guest.numero_empleado}_${guest.nombre}.png`}
                                                            className="text-blue-600 hover:text-blue-900 p-1"
                                                            title="Descargar QR"
                                                        >
                                                            <QrCodeIcon className="w-4 h-4" />
                                                        </a>
                                                        
                                                        <Link
                                                            href={route('events.guests.edit', [event.id, guest.id])}
                                                            className="text-yellow-600 hover:text-yellow-900 p-1"
                                                            title="Editar"
                                                        >
                                                            <PencilIcon className="w-4 h-4" />
                                                        </Link>
                                                        
                                                        <button
                                                            onClick={() => handleDelete(guest)}
                                                            disabled={processing}
                                                            className="text-red-600 hover:text-red-900 p-1 disabled:opacity-50"
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
                                    <div className="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                                        <div className="flex items-center justify-between">
                                            <div className="text-sm text-gray-700">
                                                Mostrando {guests.from} a {guests.to} de {guests.total} resultados
                                            </div>
                                            <div className="flex space-x-2">
                                                {guests.links.map((link, index) => (
                                                    link.url ? (
                                                        <Link
                                                            key={index}
                                                            href={link.url}
                                                            className={`px-3 py-2 text-sm border rounded ${
                                                                link.active 
                                                                    ? 'bg-blue-50 border-blue-300 text-blue-700' 
                                                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                                                            }`}
                                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                                        />
                                                    ) : (
                                                        <span
                                                            key={index}
                                                            className="px-3 py-2 text-sm border rounded bg-gray-100 border-gray-300 text-gray-400"
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