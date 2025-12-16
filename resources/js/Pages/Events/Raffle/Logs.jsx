import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Eye, CheckCircle, Clock, Download, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';

export default function RaffleLogs({ auth, event, logs, total }) {
    const [sortConfig, setSortConfig] = useState({ key: null, direction: 'asc' });
    const [filterConfirmed, setFilterConfirmed] = useState(null); // null = todos, true = solo confirmados, false = solo reemplazados

    // Filtrar logs
    const filteredLogs = useMemo(() => {
        let filtered = [...logs];
        
        if (filterConfirmed !== null) {
            filtered = filtered.filter(log => log.confirmed === filterConfirmed);
        }
        
        return filtered;
    }, [logs, filterConfirmed]);

    // Detectar guests que aparecen múltiples veces como GANADORES CONFIRMADOS
    const guestWinnerCounts = useMemo(() => {
        const counts = {};
        filteredLogs.forEach(log => {
            const guestId = log.guest?.id;
            // Solo contar entradas confirmadas (ganadores)
            if (guestId && log.confirmed) {
                counts[guestId] = (counts[guestId] || 0) + 1;
            }
        });
        return counts;
    }, [filteredLogs]);

    // Función para verificar si un guest aparece múltiples veces como ganador confirmado
    const isDuplicateGuest = (log) => {
        const guestId = log.guest?.id;
        // Solo marcar como duplicado si es un ganador confirmado Y aparece más de una vez como ganador
        return guestId && log.confirmed && guestWinnerCounts[guestId] > 1;
    };

    // Ordenar logs
    const sortedLogs = useMemo(() => {
        if (!sortConfig.key) {
            return filteredLogs;
        }

        const sorted = [...filteredLogs].sort((a, b) => {
            let aValue = a[sortConfig.key];
            let bValue = b[sortConfig.key];

            // Manejar valores anidados
            if (sortConfig.key === 'guest_name') {
                aValue = a.guest?.name || '';
                bValue = b.guest?.name || '';
            } else if (sortConfig.key === 'guest_employee_number') {
                aValue = a.guest?.employee_number || '';
                bValue = b.guest?.employee_number || '';
            } else if (sortConfig.key === 'guest_compania') {
                aValue = a.guest?.compania || '';
                bValue = b.guest?.compania || '';
            } else if (sortConfig.key === 'guest_categoria_rifa') {
                aValue = a.guest?.categoria_rifa || '';
                bValue = b.guest?.categoria_rifa || '';
            } else if (sortConfig.key === 'guest_descripcion') {
                aValue = a.guest?.descripcion || '';
                bValue = b.guest?.descripcion || '';
            } else if (sortConfig.key === 'prize_name') {
                aValue = a.prize?.name || '';
                bValue = b.prize?.name || '';
            }

            // Comparar valores
            if (aValue < bValue) {
                return sortConfig.direction === 'asc' ? -1 : 1;
            }
            if (aValue > bValue) {
                return sortConfig.direction === 'asc' ? 1 : -1;
            }
            return 0;
        });

        return sorted;
    }, [filteredLogs, sortConfig]);

    const handleSort = (key) => {
        setSortConfig(prevConfig => {
            if (prevConfig.key === key) {
                // Si ya está ordenando por esta columna, cambiar dirección
                return {
                    key,
                    direction: prevConfig.direction === 'asc' ? 'desc' : 'asc'
                };
            } else {
                // Nueva columna, ordenar ascendente
                return {
                    key,
                    direction: 'asc'
                };
            }
        });
    };

    const getSortIcon = (key) => {
        if (sortConfig.key !== key) {
            return <ArrowUpDown className="h-4 w-4 ml-1 inline text-gray-400" />;
        }
        return sortConfig.direction === 'asc' 
            ? <ArrowUp className="h-4 w-4 ml-1 inline text-blue-500" />
            : <ArrowDown className="h-4 w-4 ml-1 inline text-blue-500" />;
    };

    const handleExportCSV = () => {
        window.location.href = route('events.raffle.export-winners', event.id);
    };

    const confirmedCount = logs.filter(log => log.confirmed).length;
    const replacedCount = logs.filter(log => !log.confirmed).length;

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Logs de Rifas
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {event.name} - {event.event_date}
                            </p>
                        </div>
                    </div>
                    <Button
                        onClick={handleExportCSV}
                        className="bg-green-600 hover:bg-green-700 text-white"
                    >
                        <Download className="h-4 w-4 mr-2" />
                        Descargar CSV
                    </Button>
                </div>
            }
        >
            <Head title={`Logs de Rifas - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Resumen */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <Eye className="h-8 w-8 text-blue-500 mr-3" />
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                            Registros de Selección de Ganadores
                                        </h3>
                                        <p className="text-sm text-gray-600 dark:text-gray-300">
                                            {total} registros totales
                                            {confirmedCount > 0 && ` • ${confirmedCount} ganadores confirmados`}
                                            {replacedCount > 0 && ` • ${replacedCount} reemplazados`}
                                        </p>
                                    </div>
                                </div>
                                <div className="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                    {total}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Filtros */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-4">
                            <div className="flex items-center gap-4">
                                <span className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Filtrar por estado:
                                </span>
                                <div className="flex gap-2">
                                    <Button
                                        variant={filterConfirmed === null ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => setFilterConfirmed(null)}
                                    >
                                        Todos ({total})
                                    </Button>
                                    <Button
                                        variant={filterConfirmed === true ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => setFilterConfirmed(true)}
                                    >
                                        <CheckCircle className="h-4 w-4 mr-1" />
                                        Ganadores ({confirmedCount})
                                    </Button>
                                    <Button
                                        variant={filterConfirmed === false ? "default" : "outline"}
                                        size="sm"
                                        onClick={() => setFilterConfirmed(false)}
                                    >
                                        <Clock className="h-4 w-4 mr-1" />
                                        Reemplazados ({replacedCount})
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tabla de Logs */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Historial Completo ({sortedLogs.length} registros)
                            </h3>

                            {sortedLogs.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('raffle_type')}
                                                >
                                                    <div className="flex items-center">
                                                        Tipo de Rifa
                                                        {getSortIcon('raffle_type')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('guest_employee_number')}
                                                >
                                                    <div className="flex items-center">
                                                        Número de Empleado
                                                        {getSortIcon('guest_employee_number')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('guest_name')}
                                                >
                                                    <div className="flex items-center">
                                                        Nombre del Ganador
                                                        {getSortIcon('guest_name')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('guest_compania')}
                                                >
                                                    <div className="flex items-center">
                                                        Empresa
                                                        {getSortIcon('guest_compania')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('guest_categoria_rifa')}
                                                >
                                                    <div className="flex items-center">
                                                        Categoría
                                                        {getSortIcon('guest_categoria_rifa')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('guest_descripcion')}
                                                >
                                                    <div className="flex items-center">
                                                        Descripción
                                                        {getSortIcon('guest_descripcion')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('prize_name')}
                                                >
                                                    <div className="flex items-center">
                                                        Premio
                                                        {getSortIcon('prize_name')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('confirmed')}
                                                >
                                                    <div className="flex items-center">
                                                        Estado
                                                        {getSortIcon('confirmed')}
                                                    </div>
                                                </th>
                                                <th 
                                                    scope="col" 
                                                    className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600"
                                                    onClick={() => handleSort('created_at')}
                                                >
                                                    <div className="flex items-center">
                                                        Fecha y Hora
                                                        {getSortIcon('created_at')}
                                                    </div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {sortedLogs.map((log) => {
                                                const isDuplicate = isDuplicateGuest(log);
                                                return (
                                                <tr 
                                                    key={log.id} 
                                                    className={`hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors ${
                                                        isDuplicate 
                                                            ? 'bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500' 
                                                            : log.confirmed 
                                                                ? 'bg-green-50 dark:bg-green-900/20' 
                                                                : 'bg-yellow-50 dark:bg-yellow-900/20'
                                                    }`}
                                                >
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900 dark:text-white">
                                                            {log.raffle_type === 'public' ? 'Pública' : 'General'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {log.guest?.employee_number || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white flex items-center">
                                                            {log.guest?.name || 'N/A'}
                                                            {isDuplicate && (
                                                                <span className="ml-2 px-2 py-0.5 text-xs font-semibold bg-red-500 text-white rounded" title={`Este invitado aparece ${guestWinnerCounts[log.guest?.id]} veces como ganador confirmado`}>
                                                                    DUPLICADO
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-500 dark:text-gray-300">
                                                            {log.guest?.compania || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-500 dark:text-gray-300">
                                                            {log.guest?.categoria_rifa || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-500 dark:text-gray-300">
                                                            {log.guest?.descripcion || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900 dark:text-white">
                                                            {log.prize?.name || 'N/A'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {log.confirmed ? (
                                                            <span className="text-green-600 dark:text-green-400 font-medium flex items-center">
                                                                <CheckCircle className="h-4 w-4 mr-1" />
                                                                Ganador
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-500 dark:text-gray-400 font-medium flex items-center">
                                                                <Clock className="h-4 w-4 mr-1" />
                                                                Reemplazado
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <Clock className="h-4 w-4 text-gray-400 mr-2" />
                                                            <div className="text-sm text-gray-900 dark:text-white">
                                                                {log.created_at}
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Eye className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        No hay registros
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        {filterConfirmed !== null 
                                            ? 'No se encontraron registros con el filtro seleccionado.'
                                            : 'No se encontraron logs de rifas para este evento.'}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Nota informativa */}
                    <div className="mt-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                        <div className="flex">
                            <div className="flex-shrink-0">
                                <svg className="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm text-blue-700 dark:text-blue-300">
                                    <strong>Nota:</strong> Esta información muestra todos los registros de selección de ganadores en las rifas. 
                                    Los registros marcados como <span className="font-semibold">"Ganador"</span> son los ganadores actuales confirmados. 
                                    Los registros marcados como <span className="font-semibold">"Reemplazado"</span> son ganadores que fueron seleccionados inicialmente pero luego fueron reemplazados por otros participantes.
                                    <br />
                                    <strong>Funcionalidades:</strong> Haz clic en cualquier encabezado de columna para ordenar. Usa los filtros para ver solo ganadores confirmados o reemplazados. El botón de descarga CSV exporta todos los registros tal como se muestran en la tabla (incluyendo ganadores y reemplazados).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
