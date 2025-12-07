import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    CalendarIcon, 
    MapPinIcon, 
    UsersIcon,
    ChartBarIcon,
    QrCodeIcon,
    DocumentArrowUpIcon,
    EyeIcon,
    PencilIcon,
    ClockIcon,
    TrophyIcon,
    EnvelopeIcon,
    GiftIcon
} from '@heroicons/react/24/outline';
import { useState, useEffect } from 'react';

export default function Show({ auth, event, statistics, recent_attendances }) {
    const [liveStats, setLiveStats] = useState(statistics);
    const [recentAttendances, setRecentAttendances] = useState(recent_attendances || []);
    const [lastUpdated, setLastUpdated] = useState(null);

    // Actualizar estadísticas y asistencias cada 10 segundos
    useEffect(() => {
        const interval = setInterval(async () => {
            try {
                const response = await fetch(route('events.statistics', event.id));
                if (response.ok) {
                    const data = await response.json();
                    setLiveStats(data);
                    
                    // Actualizar lista de asistencias si viene en la respuesta
                    if (data.recent_attendances) {
                        setRecentAttendances(data.recent_attendances);
                    }
                    
                    setLastUpdated(new Date().toLocaleTimeString('es-MX', { 
                        timeZone: 'America/Mexico_City',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    }));
                }
            } catch (error) {
                // Error al actualizar estadísticas
            }
        }, 10000); // Cada 10 segundos

        return () => clearInterval(interval);
    }, [event.id]);

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            timeZone: 'America/Mexico_City',
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getAttendanceRateColor = (rate) => {
        if (rate >= 80) return 'text-green-600 bg-green-50';
        if (rate >= 60) return 'text-yellow-600 bg-yellow-50';
        if (rate >= 40) return 'text-orange-600 bg-orange-50';
        return 'text-red-600 bg-red-50';
    };

    const hourlyData = Object.entries(liveStats?.hourly_attendance || {}).map(([hour, count]) => ({
        hour: `${hour}:00`,
        count
    }));

    const maxHourlyCount = Math.max(...hourlyData.map(d => d.count), 1);

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div className="flex items-start sm:items-center">
                        <Link
                            href={route('events.index')}
                            className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white flex-shrink-0"
                        >
                            ← Volver
                        </Link>
                        <div>
                            <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-white leading-tight">
                                {event.name}
                            </h2>
                            <div className="flex flex-col sm:flex-row sm:items-center mt-1 text-xs sm:text-sm text-gray-600 dark:text-gray-300 gap-1 sm:gap-0">
                                <div className="flex items-center">
                                    <CalendarIcon className="w-4 h-4 mr-1" />
                                    {event.event_date}
                                </div>
                                <div className="flex items-center sm:ml-4">
                                    <MapPinIcon className="w-4 h-4 mr-1" />
                                    <span className="truncate">{event.location}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div className="flex flex-wrap items-center gap-2">
                        <Link
                            href={route('events.edit', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                            style={{ backgroundColor: '#B2B4B2' }}
                        >
                            <PencilIcon className="w-4 h-4 sm:mr-2" />
                            <span className="hidden sm:inline">Editar</span>
                        </Link>
                        
                        <Link
                            href={route('events.guests.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                            style={{ backgroundColor: '#0076A8' }}
                        >
                            <UsersIcon className="w-4 h-4 sm:mr-2" />
                            <span className="hidden sm:inline">Invitados</span>
                        </Link>
                        
                        <Link
                            href={route('events.statistics.report', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                            style={{ backgroundColor: '#FFB600' }}
                        >
                            <ChartBarIcon className="w-4 h-4 sm:mr-2" />
                            <span className="hidden sm:inline">Estadísticas</span>
                        </Link>
                        
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-transparent shadow-sm text-sm leading-4 font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                            style={{ backgroundColor: '#D22730' }}
                        >
                            <GiftIcon className="w-4 h-4 sm:mr-2" />
                            <span className="hidden sm:inline">Rifas</span>
                        </Link>
                        
                        {liveStats.overview?.total_guests > 0 && (
                            <Link
                                href={route('events.attendance.scanner', event.id)}
                                className="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:opacity-90 transition-opacity"
                                style={{ backgroundColor: '#BABC16' }}
                            >
                                <QrCodeIcon className="w-4 h-4 sm:mr-2" />
                                <span className="hidden sm:inline">Escáner</span>
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={event.name} />

            <div className="py-6 sm:py-12">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4 sm:space-y-6">
                    
                    {/* URL Pública del Evento */}
                    <div className="overflow-hidden shadow-lg sm:rounded-lg" style={{ background: 'linear-gradient(to right, #0076A8, #2D8C9E, #9CDBD9)' }}>
                        <div className="p-4 sm:p-6">
                            <div className="flex flex-col">
                                <div className="flex-1">
                                    <h3 className="text-base sm:text-lg font-semibold text-white mb-2 flex items-center">
                                        <svg className="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        URL Pública de Registro
                                    </h3>
                                    <p className="text-blue-100 text-xs sm:text-sm mb-3">
                                        Comparte este enlace con tus invitados para que puedan acceder con su credencial
                                    </p>
                                    <div className="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
                                        <input
                                            type="text"
                                            readOnly
                                            value={event.public_url}
                                            className="flex-1 px-3 sm:px-4 py-2 bg-white/10 border border-white/20 rounded-lg text-white text-sm placeholder-blue-200 focus:outline-none focus:ring-2 focus:ring-white/50"
                                            onClick={(e) => e.target.select()}
                                        />
                                        <div className="flex gap-2">
                                            <button
                                                onClick={() => {
                                                    navigator.clipboard.writeText(event.public_url);
                                                    alert('¡Enlace copiado al portapapeles!');
                                                }}
                                                className="flex-1 sm:flex-none px-4 py-2 bg-white text-blue-600 text-sm font-semibold rounded-lg hover:bg-blue-50 transition duration-200"
                                            >
                                                Copiar
                                            </button>
                                            <a
                                                href={event.public_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="flex-1 sm:flex-none px-4 py-2 bg-white/20 text-white text-sm font-semibold rounded-lg hover:bg-white/30 transition duration-200 text-center"
                                            >
                                                Abrir
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Información del evento */}
                    {event.description && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-2">
                                    Descripción
                                </h3>
                                <p className="text-sm sm:text-base text-gray-700">{event.description}</p>
                            </div>
                        </div>
                    )}

                    {/* Estadísticas generales */}
                    <div className="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UsersIcon className="h-6 w-6 sm:h-8 sm:w-8 text-blue-500" />
                                    </div>
                                    <div className="ml-3 sm:ml-4">
                                        <div className="text-xl sm:text-2xl font-bold text-gray-900">
                                            {liveStats.overview?.total_guests || 0}
                                        </div>
                                        <div className="text-xs sm:text-sm text-gray-600">Total Invitados</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <EyeIcon className="h-6 w-6 sm:h-8 sm:w-8 text-green-500" />
                                    </div>
                                    <div className="ml-3 sm:ml-4">
                                        <div className="text-xl sm:text-2xl font-bold text-gray-900">
                                            {liveStats.overview?.total_attendances || 0}
                                        </div>
                                        <div className="text-xs sm:text-sm text-gray-600">Asistencias</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ChartBarIcon className="h-6 w-6 sm:h-8 sm:w-8 text-purple-500" />
                                    </div>
                                    <div className="ml-3 sm:ml-4">
                                        <div className={`text-lg sm:text-2xl font-bold px-2 sm:px-3 py-1 rounded-full ${getAttendanceRateColor(liveStats.overview?.attendance_rate || 0)}`}>
                                            {liveStats.overview?.attendance_rate || 0}%
                                        </div>
                                        <div className="text-xs sm:text-sm text-gray-600">Tasa Asistencia</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <Link
                                    href={route('events.realtime-logs', event.id)}
                                    className="flex items-center justify-between group hover:bg-gray-50 -m-4 sm:-m-6 p-4 sm:p-6 rounded-lg transition-colors"
                                >
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <svg className="h-6 w-6 sm:h-8 sm:w-8 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div className="ml-3 sm:ml-4">
                                            <div className="text-sm sm:text-lg font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">
                                                Registros
                                            </div>
                                            <div className="text-xs sm:text-sm text-gray-500">
                                                {liveStats.overview?.registered_guests || 0} registrados ({liveStats.overview?.registration_rate || 0}%)
                                            </div>
                                        </div>
                                    </div>
                                    <svg className="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 group-hover:text-indigo-500 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    {/* Progress bar de asistencia */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-3 sm:mb-2 gap-1">
                                <h3 className="text-base sm:text-lg font-medium text-gray-900">
                                    Progreso de Asistencia
                                </h3>
                                {lastUpdated && (
                                    <span className="text-xs text-gray-500">
                                        Actualizado: {lastUpdated}
                                    </span>
                                )}
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-3 sm:h-4 mb-2">
                                <div 
                                    className="h-3 sm:h-4 rounded-full transition-all duration-1000 flex items-center justify-end pr-1 sm:pr-2"
                                    style={{ 
                                        background: 'linear-gradient(to right, #2D8C9E, #9CDBD9)',
                                        width: `${Math.max(liveStats.overview?.attendance_rate || 0, 5)}%` 
                                    }}
                                >
                                    {liveStats.overview?.attendance_rate > 10 && (
                                        <span className="text-[10px] sm:text-xs font-medium text-white">
                                            {liveStats.overview?.attendance_rate}%
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="flex justify-between text-xs sm:text-sm text-gray-600">
                                <span>{liveStats.overview?.total_attendances || 0} asistieron</span>
                                <span>{liveStats.overview?.pending_guests || 0} pendientes</span>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        {/* Flujo de asistencia por horas */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">
                                    Flujo de Asistencia por Hora
                                </h3>
                                
                                {hourlyData.length > 0 ? (
                                    <div className="space-y-2 sm:space-y-3">
                                        {hourlyData.map(({ hour, count }) => (
                                            <div key={hour} className="flex items-center">
                                                <div className="w-12 sm:w-16 text-xs sm:text-sm text-gray-600 font-medium">
                                                    {hour}
                                                </div>
                                                <div className="flex-1 ml-2 sm:ml-4">
                                                    <div className="flex items-center">
                                                        <div className="flex-1 bg-gray-200 rounded-full h-5 sm:h-6">
                                                            <div
                                                                className="h-5 sm:h-6 rounded-full flex items-center justify-end pr-1 sm:pr-2 transition-all duration-300"
                                                                style={{
                                                                    backgroundColor: '#2D8C9E',
                                                                    width: `${Math.max((count / maxHourlyCount) * 100, count > 0 ? 10 : 0)}%`
                                                                }}
                                                            >
                                                                {count > 0 && (
                                                                    <span className="text-[10px] sm:text-xs font-medium text-white">
                                                                        {count}
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-6 sm:py-8 text-gray-500">
                                        <ClockIcon className="mx-auto h-6 w-6 sm:h-8 sm:w-8 mb-2" />
                                        <p className="text-sm">Sin datos de asistencia aún</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Asistencia por área */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-4 sm:p-6">
                                <h3 className="text-base sm:text-lg font-medium text-gray-900 mb-3 sm:mb-4">
                                    Asistencia por Área Laboral
                                </h3>
                                
                                {Object.keys(liveStats?.attendance_by_area || {}).length > 0 ? (
                                    <div className="space-y-2 sm:space-y-3">
                                        {Object.entries(liveStats.attendance_by_area)
                                            .sort(([,a], [,b]) => b - a)
                                            .map(([area, count]) => {
                                                const maxCount = Math.max(...Object.values(liveStats.attendance_by_area));
                                                const percentage = maxCount > 0 ? (count / maxCount) * 100 : 0;
                                                
                                                return (
                                                    <div key={area} className="flex items-center">
                                                        <div className="w-24 sm:w-32 text-xs sm:text-sm text-gray-600 font-medium truncate">
                                                            {area}
                                                        </div>
                                                        <div className="flex-1 ml-2 sm:ml-4">
                                                            <div className="flex items-center">
                                                                <div className="flex-1 bg-gray-200 rounded-full h-5 sm:h-6">
                                                                    <div
                                                                        className="h-5 sm:h-6 rounded-full flex items-center justify-end pr-1 sm:pr-2 transition-all duration-300"
                                                                        style={{
                                                                            backgroundColor: '#0076A8',
                                                                            width: `${Math.max(percentage, count > 0 ? 15 : 0)}%`
                                                                        }}
                                                                    >
                                                                        <span className="text-[10px] sm:text-xs font-medium text-white">
                                                                            {count}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                );
                                            })
                                        }
                                    </div>
                                ) : (
                                    <div className="text-center py-6 sm:py-8 text-gray-500">
                                        <ChartBarIcon className="mx-auto h-6 w-6 sm:h-8 sm:w-8 mb-2" />
                                        <p className="text-sm">Sin datos por área aún</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Últimas asistencias */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-4 sm:p-6">
                            <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-3 sm:mb-4 gap-2">
                                <h3 className="text-base sm:text-lg font-medium text-gray-900">
                                    Últimas Asistencias Registradas
                                </h3>
                                <Link
                                    href={route('events.attendance.index', event.id)}
                                    className="text-xs sm:text-sm text-blue-600 hover:text-blue-900"
                                >
                                    Ver todas →
                                </Link>
                            </div>
                            
                            {recentAttendances && recentAttendances.length > 0 ? (
                                <div className="flow-root">
                                    <ul className="-my-3 sm:-my-5 divide-y divide-gray-200">
                                        {recentAttendances.map((attendance, index) => (
                                            <li key={attendance.id || index} className="py-3 sm:py-4">
                                                <div className="flex items-center space-x-3 sm:space-x-4">
                                                    <div className="flex-shrink-0">
                                                        <div className="h-7 w-7 sm:h-8 sm:w-8 rounded-full bg-green-100 flex items-center justify-center">
                                                            <EyeIcon className="h-3 w-3 sm:h-4 sm:w-4 text-green-600" />
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-xs sm:text-sm font-medium text-gray-900 truncate">
                                                            {attendance.guest_name}
                                                        </p>
                                                        <p className="text-xs sm:text-sm text-gray-500 truncate">
                                                            {attendance.employee_number}
                                                        </p>
                                                    </div>
                                                    <div className="flex-shrink-0 text-xs sm:text-sm text-gray-500 hidden sm:block">
                                                        {attendance.attended_at}
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ) : (
                                <div className="text-center py-6 sm:py-8 text-gray-500">
                                    <UsersIcon className="mx-auto h-6 w-6 sm:h-8 sm:w-8 mb-2" />
                                    <p className="text-sm">No hay asistencias registradas aún</p>
                                    {liveStats.overview?.total_guests === 0 ? (
                                        <div className="mt-4">
                                            <Link
                                                href={route('events.guests.import', event.id)}
                                                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-xs sm:text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                                Importar Invitados
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="mt-4">
                                            <Link
                                                href={route('events.attendance.scanner', event.id)}
                                                className="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs sm:text-xs text-white uppercase tracking-widest hover:bg-green-700 w-full sm:w-auto justify-center"
                                            >
                                                <QrCodeIcon className="w-4 h-4 mr-2" />
                                                Iniciar Escáner
                                            </Link>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}