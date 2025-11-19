import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    CalendarIcon, 
    MapPinIcon, 
    UsersIcon,
    ChartBarIcon,
    ClockIcon,
    TrophyIcon,
    DocumentArrowDownIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';

export default function StatisticsReport({ auth, event, statistics, attendances }) {
    const formatDate = (dateString) => {
        return new Date(dateString).toLocaleDateString('es-MX', {
            timeZone: 'America/Mexico_City',
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const getAttendanceRateColor = (rate) => {
        if (rate >= 80) return 'text-green-600 bg-green-50';
        if (rate >= 60) return 'text-yellow-600 bg-yellow-50';
        if (rate >= 40) return 'text-orange-600 bg-orange-50';
        return 'text-red-600 bg-red-50';
    };

    const hourlyData = Object.entries(statistics?.hourly_attendance || {}).map(([hour, count]) => ({
        hour: `${hour}:00`,
        count
    }));

    const maxHourlyCount = Math.max(...hourlyData.map(d => d.count), 1);

    const handleGeneratePDF = async () => {
        try {
            const response = await fetch(route('events.statistics.pdf', event.id));
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `estadisticas-${event.name.replace(/\s+/g, '-').toLowerCase()}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        } catch (error) {
            console.error('Error generando PDF:', error);
            alert('Hubo un error al generar el PDF');
        }
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
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Reporte de Estadísticas - {event.name}
                            </h2>
                            <div className="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-300">
                                <CalendarIcon className="w-4 h-4 mr-1" />
                                {event.event_date}
                                <MapPinIcon className="w-4 h-4 ml-4 mr-1" />
                                {event.location}
                            </div>
                        </div>
                    </div>
                    
                    <button
                        onClick={handleGeneratePDF}
                        className="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700"
                    >
                        <DocumentArrowDownIcon className="w-4 h-4 mr-2" />
                        Generar PDF
                    </button>
                </div>
            }
        >
            <Head title={`Estadísticas - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6" id="statistics-report">
                    
                    {/* Resumen General */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-6 flex items-center">
                                <ChartBarIcon className="w-6 h-6 mr-2 text-purple-600" />
                                Resumen General del Evento
                            </h3>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <div className="bg-blue-50 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-blue-600 font-medium">Total Invitados</p>
                                            <p className="text-3xl font-bold text-blue-900 mt-1">
                                                {statistics.overview?.total_guests || 0}
                                            </p>
                                        </div>
                                        <UsersIcon className="h-12 w-12 text-blue-400" />
                                    </div>
                                </div>

                                <div className="bg-green-50 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-green-600 font-medium">Asistencias</p>
                                            <p className="text-3xl font-bold text-green-900 mt-1">
                                                {statistics.overview?.total_attendances || 0}
                                            </p>
                                        </div>
                                        <UsersIcon className="h-12 w-12 text-green-400" />
                                    </div>
                                </div>

                                <div className="bg-yellow-50 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-yellow-600 font-medium">Pendientes</p>
                                            <p className="text-3xl font-bold text-yellow-900 mt-1">
                                                {statistics.overview?.pending_guests || 0}
                                            </p>
                                        </div>
                                        <ClockIcon className="h-12 w-12 text-yellow-400" />
                                    </div>
                                </div>

                                <div className="bg-purple-50 rounded-lg p-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-purple-600 font-medium">Tasa Asistencia</p>
                                            <p className="text-3xl font-bold text-purple-900 mt-1">
                                                {statistics.overview?.attendance_rate || 0}%
                                            </p>
                                        </div>
                                        <ChartBarIcon className="h-12 w-12 text-purple-400" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Información de Rifas */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-6 flex items-center">
                                <TrophyIcon className="w-6 h-6 mr-2 text-yellow-600" />
                                Información de Rifas
                            </h3>
                            
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="bg-yellow-50 rounded-lg p-4">
                                    <p className="text-sm text-yellow-600 font-medium">Total Premios</p>
                                    <p className="text-3xl font-bold text-yellow-900 mt-1">
                                        {statistics.overview?.total_prizes || 0}
                                    </p>
                                </div>

                                <div className="bg-orange-50 rounded-lg p-4">
                                    <p className="text-sm text-orange-600 font-medium">Stock Total</p>
                                    <p className="text-3xl font-bold text-orange-900 mt-1">
                                        {statistics.overview?.total_prize_stock || 0}
                                    </p>
                                </div>

                                <div className="bg-pink-50 rounded-lg p-4">
                                    <p className="text-sm text-pink-600 font-medium">Participantes en Rifas</p>
                                    <p className="text-3xl font-bold text-pink-900 mt-1">
                                        {statistics.overview?.active_raffle_entries || 0}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Flujo de Asistencia por Hora */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-6">
                                Flujo de Asistencia por Hora
                            </h3>
                            
                            {hourlyData.length > 0 ? (
                                <div className="space-y-3">
                                    {hourlyData.map(({ hour, count }) => (
                                        <div key={hour} className="flex items-center">
                                            <div className="w-20 text-sm text-gray-600 font-medium">
                                                {hour}
                                            </div>
                                            <div className="flex-1 ml-4">
                                                <div className="flex items-center">
                                                    <div className="flex-1 bg-gray-200 rounded-full h-8">
                                                        <div
                                                            className="bg-gradient-to-r from-blue-500 to-blue-600 h-8 rounded-full flex items-center justify-end pr-3 transition-all duration-300"
                                                            style={{
                                                                width: `${Math.max((count / maxHourlyCount) * 100, count > 0 ? 10 : 0)}%`
                                                            }}
                                                        >
                                                            {count > 0 && (
                                                                <span className="text-sm font-bold text-white">
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
                                    <ClockIcon className="mx-auto h-12 w-12 mb-2" />
                                    <p>Sin datos de asistencia por hora</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Asistencia por Área Laboral */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-6">
                                Asistencia por Área Laboral
                            </h3>
                            
                            {Object.keys(statistics?.attendance_by_work_area || {}).length > 0 ? (
                                <div className="space-y-3">
                                    {Object.entries(statistics.attendance_by_work_area)
                                        .sort(([,a], [,b]) => b - a)
                                        .map(([area, count]) => {
                                            const maxCount = Math.max(...Object.values(statistics.attendance_by_work_area));
                                            const percentage = maxCount > 0 ? (count / maxCount) * 100 : 0;
                                            
                                            return (
                                                <div key={area} className="flex items-center">
                                                    <div className="w-48 text-sm text-gray-600 font-medium truncate" title={area}>
                                                        {area}
                                                    </div>
                                                    <div className="flex-1 ml-4">
                                                        <div className="flex items-center">
                                                            <div className="flex-1 bg-gray-200 rounded-full h-8">
                                                                <div
                                                                    className="bg-gradient-to-r from-green-500 to-green-600 h-8 rounded-full flex items-center justify-end pr-3 transition-all duration-300"
                                                                    style={{
                                                                        width: `${Math.max(percentage, count > 0 ? 15 : 0)}%`
                                                                    }}
                                                                >
                                                                    <span className="text-sm font-bold text-white">
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
                                    <ChartBarIcon className="mx-auto h-12 w-12 mb-2" />
                                    <p>Sin datos por área laboral</p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Lista Completa de Asistencias */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-xl font-bold text-gray-900 mb-6">
                                Registro Completo de Asistencias
                            </h3>
                            
                            {attendances && attendances.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    #
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Nombre
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    No. Empleado
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Área
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Hora de Registro
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {attendances.map((attendance, index) => (
                                                <tr key={attendance.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {index + 1}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {attendance.guest_name}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {attendance.employee_number}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {attendance.work_area}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {attendance.attended_at}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <UsersIcon className="mx-auto h-12 w-12 mb-2" />
                                    <p>No hay asistencias registradas</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
