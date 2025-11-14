import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { 
    UserIcon,
    CheckCircleIcon,
    XCircleIcon,
    QrCodeIcon,
    PencilIcon,
    ArrowLeftIcon
} from '@heroicons/react/24/outline';

export default function Show({ auth, event, guest }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center">
                        <Link
                            href={route('events.guests.index', event.id)}
                            className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                {guest.full_name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                Detalles del invitado - {event.name}
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        {/* <Link
                            href={route('events.guests.download-qr', [event.id, guest.id])}
                            className="inline-flex items-center px-3 py-2 border border-blue-300 dark:border-blue-600 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900 hover:bg-blue-100 dark:hover:bg-blue-800"
                        >
                            <QrCodeIcon className="w-4 h-4 mr-2" />
                            Descargar QR
                        </Link> */}
                        
                        <Link
                            href={route('events.guests.edit', [event.id, guest.id])}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600"
                        >
                            <PencilIcon className="w-4 h-4 mr-2" />
                            Editar
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`${guest.full_name} - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Información principal */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center mb-6">
                                <div className="flex-shrink-0 mr-4">
                                    <div className="h-16 w-16 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <UserIcon className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-2xl font-bold text-gray-900 dark:text-white">
                                        {guest.full_name}
                                    </h3>
                                    <p className="text-lg text-gray-600 dark:text-gray-300">
                                        {guest.numero_empleado} - {guest.puesto}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    {guest.has_attended ? (
                                        <div className="flex items-center text-green-600 dark:text-green-400">
                                            <CheckCircleIcon className="h-6 w-6 mr-2" />
                                            <span className="font-medium">Asistió</span>
                                        </div>
                                    ) : (
                                        <div className="flex items-center text-gray-400 dark:text-gray-500">
                                            <XCircleIcon className="h-6 w-6 mr-2" />
                                            <span className="font-medium">No asistió</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Información personal */}
                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                        Información Personal
                                    </h4>
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Nombre Completo
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.nombre_completo}
                                            </dd>
                                        </div>
                                        {guest.correo && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    Correo Electrónico
                                                </dt>
                                                <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                    {guest.correo}
                                                </dd>
                                            </div>
                                        )}
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Número de Empleado
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.numero_empleado}
                                            </dd>
                                        </div>
                                        {guest.fecha_alta && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    Fecha de Alta
                                                </dt>
                                                <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                    {guest.fecha_alta}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>

                                {/* Información laboral */}
                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                        Información Laboral
                                    </h4>
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Compañía
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.compania}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Puesto
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.puesto}
                                            </dd>
                                        </div>
                                        {guest.nivel_de_puesto && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                    Nivel de Puesto
                                                </dt>
                                                <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                    {guest.nivel_de_puesto}
                                                </dd>
                                            </div>
                                        )}
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Localidad
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.localidad}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500 dark:text-gray-400">
                                                Registrado
                                            </dt>
                                            <dd className="text-sm text-gray-900 dark:text-gray-200">
                                                {guest.created_at}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>

                            {/* Descripción */}
                            {guest.descripcion && (
                                <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                    <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Descripción
                                    </h4>
                                    <p className="text-sm text-gray-900 dark:text-gray-200">
                                        {guest.descripcion}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Categoría de rifa */}
                    {guest.categoria_rifa && (
                        <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div className="p-6">
                                <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                    Categoría de Rifa
                                </h4>
                                <span className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                    {guest.categoria_rifa}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Estado de asistencia */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Estado de Asistencia
                            </h4>
                            {guest.has_attended ? (
                                <div className="flex items-center text-green-600 dark:text-green-400">
                                    <CheckCircleIcon className="h-8 w-8 mr-3" />
                                    <div>
                                        <p className="font-medium text-lg">Asistencia Registrada</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-300">
                                            Registrado el: {guest.attended_at}
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex items-center text-gray-400 dark:text-gray-500">
                                    <XCircleIcon className="h-8 w-8 mr-3" />
                                    <div>
                                        <p className="font-medium text-lg">Sin Asistencia</p>
                                        <p className="text-sm text-gray-600 dark:text-gray-300">
                                            El invitado aún no ha registrado su asistencia
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Código QR */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                Código QR
                            </h4>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                        Código único: <span className="font-mono font-medium">{guest.qr_code}</span>
                                    </p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">
                                        Usa este código QR para registrar la asistencia del invitado
                                    </p>
                                </div>
                                <Link
                                    href={route('events.guests.download-qr', [event.id, guest.id])}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 dark:bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 dark:hover:bg-blue-600"
                                >
                                    <QrCodeIcon className="w-4 h-4 mr-2" />
                                    Descargar QR
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}