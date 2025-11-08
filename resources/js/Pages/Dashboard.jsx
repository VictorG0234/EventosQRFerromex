import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PlusIcon, UsersIcon, CalendarIcon, ChartBarIcon } from '@heroicons/react/24/outline';

export default function Dashboard({ auth }) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Dashboard - QR Eventos
                    </h2>
                    <Link
                        href={route('events.create')}
                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <PlusIcon className="w-4 h-4 mr-2" />
                        Crear Evento
                    </Link>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Welcome Card */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium text-gray-900 mb-2">
                                ¡Bienvenido, {auth?.user?.name || 'Usuario'}!
                            </h3>
                            <p className="text-gray-600">
                                Gestiona tus eventos, invitados y códigos QR desde este panel de control.
                            </p>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <Link
                            href={route('events.create')}
                            className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                        >
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <CalendarIcon className="h-8 w-8 text-blue-600" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Nuevo Evento
                                        </h3>
                                        <p className="text-sm text-gray-600">
                                            Crear un evento corporativo
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <Link
                            href={route('events.index')}
                            className="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow"
                        >
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <UsersIcon className="h-8 w-8 text-green-600" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Mis Eventos
                                        </h3>
                                        <p className="text-sm text-gray-600">
                                            Ver todos mis eventos
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </Link>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <ChartBarIcon className="h-8 w-8 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <h3 className="text-lg font-medium text-gray-900">
                                            Estadísticas
                                        </h3>
                                        <p className="text-sm text-gray-600">
                                            Reportes y analytics
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Getting Started Guide */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-lg font-medium text-gray-900 mb-4">
                                Guía de Inicio Rápido
                            </h3>
                            <div className="space-y-4">
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        <div className="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full">
                                            <span className="text-sm font-medium text-blue-600">1</span>
                                        </div>
                                    </div>
                                    <div className="ml-3">
                                        <h4 className="text-sm font-medium text-gray-900">
                                            Crear un Evento
                                        </h4>
                                        <p className="text-sm text-gray-600">
                                            Define los detalles básicos de tu evento corporativo
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        <div className="flex items-center justify-center w-8 h-8 bg-green-100 rounded-full">
                                            <span className="text-sm font-medium text-green-600">2</span>
                                        </div>
                                    </div>
                                    <div className="ml-3">
                                        <h4 className="text-sm font-medium text-gray-900">
                                            Importar Invitados
                                        </h4>
                                        <p className="text-sm text-gray-600">
                                            Sube un archivo CSV con la lista de invitados
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        <div className="flex items-center justify-center w-8 h-8 bg-purple-100 rounded-full">
                                            <span className="text-sm font-medium text-purple-600">3</span>
                                        </div>
                                    </div>
                                    <div className="ml-3">
                                        <h4 className="text-sm font-medium text-gray-900">
                                            Enviar Códigos QR
                                        </h4>
                                        <p className="text-sm text-gray-600">
                                            Los códigos QR se generan automáticamente y se envían por email
                                        </p>
                                    </div>
                                </div>
                                
                                <div className="flex items-start">
                                    <div className="flex-shrink-0">
                                        <div className="flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-full">
                                            <span className="text-sm font-medium text-yellow-600">4</span>
                                        </div>
                                    </div>
                                    <div className="ml-3">
                                        <h4 className="text-sm font-medium text-gray-900">
                                            Control de Asistencia
                                        </h4>
                                        <p className="text-sm text-gray-600">
                                            Usa el escáner QR para registrar asistencia en tiempo real
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
