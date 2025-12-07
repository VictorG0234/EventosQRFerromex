import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { ClockIcon, EnvelopeIcon, UserIcon } from '@heroicons/react/24/outline';

export default function RealtimeLogs({ auth, event, logs, total_logs, total_guests }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Registros en Tiempo Real
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            {event.name} - {event.date}
                        </p>
                    </div>
                    <Link
                        href={route('events.show', event.id)}
                        className="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-gray-300"
                    >
                        ← Volver al Evento
                    </Link>
                </div>
            }
        >
            <Head title={`Registros - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Resumen */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center">
                                    <EnvelopeIcon className="h-8 w-8 text-indigo-500 mr-3" />
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                            Invitaciones Enviadas
                                        </h3>
                                        <p className="text-sm text-gray-600 dark:text-gray-300">
                                            {total_logs} / {total_guests} invitaciones
                                        </p>
                                    </div>
                                </div>
                                <div className="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                                    {total_guests > 0 ? Math.round((total_logs / total_guests) * 100) : 0}%
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tabla de Logs */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Historial de Envíos
                            </h3>

                            {logs.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Fecha y Hora
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Invitado
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    Correo Electrónico
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                    ID Invitado
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                            {logs.map((log, index) => (
                                                <tr key={index} className="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <ClockIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                            <div className="text-sm text-gray-900 dark:text-white">
                                                                {log.timestamp}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <UserIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                            <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                {log.guest_name}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <div className="flex items-center">
                                                            <EnvelopeIcon className="h-5 w-5 text-gray-400 mr-2" />
                                                            <div className="text-sm text-gray-600 dark:text-gray-300">
                                                                {log.email}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                                                            #{log.guest_id}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <EnvelopeIcon className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                                        No hay registros
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        No se encontraron logs de invitaciones enviadas para este evento.
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
                                    <strong>Nota:</strong> Esta información se obtiene directamente de los logs del sistema. 
                                    Los registros muestran las invitaciones enviadas cuando los invitados validan sus credenciales 
                                    en la URL pública del evento.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
