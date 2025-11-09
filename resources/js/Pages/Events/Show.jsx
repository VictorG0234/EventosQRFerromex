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
    const [lastUpdated, setLastUpdated] = useState(null);

    // Actualizar estadísticas cada 30 segundos
    useEffect(() => {
        const interval = setInterval(async () => {
            try {
                const response = await fetch(route('events.statistics', event.id));
                if (response.ok) {
                    const data = await response.json();
                    setLiveStats(data);
                    setLastUpdated(new Date().toLocaleTimeString());
                }
            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }, 30000);

        return () => clearInterval(interval);
    }, [event.id]);

    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('es-ES', {
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
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link
                            href={route('events.index')}
                            className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            ← Volver a eventos
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                {event.name}
                            </h2>
                            <div className="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-300">
                                <CalendarIcon className="w-4 h-4 mr-1" />
                                {event.event_date}
                                <MapPinIcon className="w-4 h-4 ml-4 mr-1" />
                                {event.location}
                            </div>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                        <Link
                            href={route('events.edit', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Editar
                        </Link>
                        
                        <Link
                            href={route('events.guests.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800"
                        >
                            <UsersIcon className="w-4 h-4 mr-2" />
                            Ver Invitados
                        </Link>
                        
                        <Link
                            href={route('events.emails.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-purple-300 dark:border-purple-600 shadow-sm text-sm leading-4 font-medium rounded-md text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900 hover:bg-purple-100 dark:hover:bg-purple-800"
                        >
                            <EnvelopeIcon className="w-4 h-4 mr-2" />
                            Emails
                        </Link>
                        
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="inline-flex items-center px-3 py-2 border border-orange-300 dark:border-orange-600 shadow-sm text-sm leading-4 font-medium rounded-md text-orange-700 dark:text-orange-300 bg-orange-50 dark:bg-orange-900 hover:bg-orange-100 dark:hover:bg-orange-800"
                        >
                            <GiftIcon className="w-4 h-4 mr-2" />
                            Rifas
                        </Link>
                        
                        {liveStats.overview?.total_guests > 0 && (
                            <Link
                                href={route('events.attendance.scanner', event.id)}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                            >
                                <QrCodeIcon className="w-4 h-4 mr-2" />
                                Escáner QR
                            </Link>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={event.name} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Información del evento */}
                    {event.description && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    Descripción
                                </h3>
                                <p className="text-gray-700">{event.description}</p>
                            </div>
                        </div>
                    )}

                    {/* Estadísticas generales */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UsersIcon className="h-8 w-8 text-blue-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {liveStats.overview?.total_guests || 0}
                                        </div>
                                        <div className="text-sm text-gray-600">Total Invitados</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <EyeIcon className="h-8 w-8 text-green-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {liveStats.overview?.total_attendances || 0}
                                        </div>
                                        <div className="text-sm text-gray-600">Asistencias</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ChartBarIcon className="h-8 w-8 text-purple-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className={`text-2xl font-bold px-3 py-1 rounded-full ${getAttendanceRateColor(liveStats.overview?.attendance_rate || 0)}`}>
                                            {liveStats.overview?.attendance_rate || 0}%
                                        </div>
                                        <div className="text-sm text-gray-600">Tasa Asistencia</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <TrophyIcon className="h-8 w-8 text-yellow-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {liveStats.overview?.active_raffle_entries || 0}
                                        </div>
                                        <div className="text-sm text-gray-600">Participando en Rifas</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Progress bar de asistencia */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-2">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Progreso de Asistencia
                                </h3>
                                {lastUpdated && (
                                    <span className="text-xs text-gray-500">
                                        Actualizado: {lastUpdated}
                                    </span>
                                )}
                            </div>
                            <div className="w-full bg-gray-200 rounded-full h-4 mb-2">
                                <div 
                                    className="bg-gradient-to-r from-blue-500 to-green-500 h-4 rounded-full transition-all duration-1000 flex items-center justify-end pr-2"
                                    style={{ width: `${Math.max(liveStats.overview?.attendance_rate || 0, 5)}%` }}
                                >
                                    {liveStats.overview?.attendance_rate > 10 && (
                                        <span className="text-xs font-medium text-white">
                                            {liveStats.overview?.attendance_rate}%
                                        </span>
                                    )}
                                </div>
                            </div>
                            <div className="flex justify-between text-sm text-gray-600">
                                <span>{liveStats.overview?.total_attendances || 0} asistieron</span>
                                <span>{liveStats.overview?.pending_guests || 0} pendientes</span>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        
                        {/* Flujo de asistencia por horas */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Flujo de Asistencia por Hora
                                </h3>
                                
                                {hourlyData.length > 0 ? (
                                    <div className="space-y-3">
                                        {hourlyData.map(({ hour, count }) => (
                                            <div key={hour} className="flex items-center">
                                                <div className="w-16 text-sm text-gray-600 font-medium">
                                                    {hour}
                                                </div>
                                                <div className="flex-1 ml-4">
                                                    <div className="flex items-center">
                                                        <div className="flex-1 bg-gray-200 rounded-full h-6">
                                                            <div
                                                                className="bg-blue-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all duration-300"
                                                                style={{
                                                                    width: `${Math.max((count / maxHourlyCount) * 100, count > 0 ? 10 : 0)}%`
                                                                }}
                                                            >
                                                                {count > 0 && (
                                                                    <span className="text-xs font-medium text-white">
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
                                    <div className="text-center py-8 text-gray-500">
                                        <ClockIcon className="mx-auto h-8 w-8 mb-2" />
                                        <p>Sin datos de asistencia aún</p>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Asistencia por área */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">
                                    Asistencia por Área Laboral
                                </h3>
                                
                                {Object.keys(liveStats?.attendance_by_area || {}).length > 0 ? (
                                    <div className="space-y-3">
                                        {Object.entries(liveStats.attendance_by_area)
                                            .sort(([,a], [,b]) => b - a)
                                            .map(([area, count]) => {
                                                const maxCount = Math.max(...Object.values(liveStats.attendance_by_area));
                                                const percentage = maxCount > 0 ? (count / maxCount) * 100 : 0;
                                                
                                                return (
                                                    <div key={area} className="flex items-center">
                                                        <div className="w-32 text-sm text-gray-600 font-medium truncate">
                                                            {area}
                                                        </div>
                                                        <div className="flex-1 ml-4">
                                                            <div className="flex items-center">
                                                                <div className="flex-1 bg-gray-200 rounded-full h-6">
                                                                    <div
                                                                        className="bg-green-500 h-6 rounded-full flex items-center justify-end pr-2 transition-all duration-300"
                                                                        style={{
                                                                            width: `${Math.max(percentage, count > 0 ? 15 : 0)}%`
                                                                        }}
                                                                    >
                                                                        <span className="text-xs font-medium text-white">
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
                                    <div className="text-center py-8 text-gray-500">
                                        <ChartBarIcon className="mx-auto h-8 w-8 mb-2" />
                                        <p>Sin datos por área aún</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Últimas asistencias */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Últimas Asistencias Registradas
                                </h3>
                                <Link
                                    href={route('events.attendance.index', event.id)}
                                    className="text-sm text-blue-600 hover:text-blue-900"
                                >
                                    Ver todas →
                                </Link>
                            </div>
                            
                            {recent_attendances && recent_attendances.length > 0 ? (
                                <div className="flow-root">
                                    <ul className="-my-5 divide-y divide-gray-200">
                                        {recent_attendances.map((attendance, index) => (
                                            <li key={index} className="py-4">
                                                <div className="flex items-center space-x-4">
                                                    <div className="flex-shrink-0">
                                                        <div className="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                                            <EyeIcon className="h-4 w-4 text-green-600" />
                                                        </div>
                                                    </div>
                                                    <div className="flex-1 min-w-0">
                                                        <p className="text-sm font-medium text-gray-900 truncate">
                                                            {attendance.guest_name}
                                                        </p>
                                                        <p className="text-sm text-gray-500 truncate">
                                                            {attendance.guest_employee_number}
                                                        </p>
                                                    </div>
                                                    <div className="flex-shrink-0 text-sm text-gray-500">
                                                        {attendance.attended_at}
                                                    </div>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <UsersIcon className="mx-auto h-8 w-8 mb-2" />
                                    <p>No hay asistencias registradas aún</p>
                                    {liveStats.overview?.total_guests === 0 ? (
                                        <div className="mt-4">
                                            <Link
                                                href={route('events.guests.import', event.id)}
                                                className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                            >
                                                <DocumentArrowUpIcon className="w-4 h-4 mr-2" />
                                                Importar Invitados
                                            </Link>
                                        </div>
                                    ) : (
                                        <div className="mt-4">
                                            <Link
                                                href={route('events.attendance.scanner', event.id)}
                                                className="inline-flex items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
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