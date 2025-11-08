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
                            className="mr-4 text-gray-600 hover:text-gray-900"
                        >
                            <ArrowLeftIcon className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                                {guest.full_name}
                            </h2>
                            <p className="text-sm text-gray-600">
                                Detalles del invitado - {event.name}
                            </p>
                        </div>
                    </div>
                    
                    <div className="flex items-center space-x-2">
                        <a
                            href={`/storage/${guest.qr_code_path}`}
                            download={`QR_${guest.numero_empleado}_${guest.nombre}.png`}
                            className="inline-flex items-center px-3 py-2 border border-blue-300 shadow-sm text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100"
                        >
                            <QrCodeIcon className="w-4 h-4 mr-2" />
                            Descargar QR
                        </a>
                        
                        <Link
                            href={route('events.guests.edit', [event.id, guest.id])}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
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
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <div className="flex items-center mb-6">
                                <div className="flex-shrink-0 mr-4">
                                    <div className="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                                        <UserIcon className="h-8 w-8 text-blue-600" />
                                    </div>
                                </div>
                                <div className="flex-1">
                                    <h3 className="text-2xl font-bold text-gray-900">
                                        {guest.full_name}
                                    </h3>
                                    <p className="text-lg text-gray-600">
                                        {guest.numero_empleado} - {guest.area_laboral}
                                    </p>
                                </div>
                                <div className="flex-shrink-0">
                                    {guest.has_attended ? (
                                        <div className="flex items-center text-green-600">
                                            <CheckCircleIcon className="h-6 w-6 mr-2" />
                                            <span className="font-medium">Asistió</span>
                                        </div>
                                    ) : (
                                        <div className="flex items-center text-gray-400">
                                            <XCircleIcon className="h-6 w-6 mr-2" />
                                            <span className="font-medium">No asistió</span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Información personal */}
                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">
                                        Información Personal
                                    </h4>
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Nombre
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.nombre}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Apellido Paterno
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.apellido_p}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Apellido Materno
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.apellido_m || '-'}
                                            </dd>
                                        </div>
                                        {guest.email && (
                                            <div>
                                                <dt className="text-sm font-medium text-gray-500">
                                                    Correo Electrónico
                                                </dt>
                                                <dd className="text-sm text-gray-900">
                                                    {guest.email}
                                                </dd>
                                            </div>
                                        )}
                                    </dl>
                                </div>

                                {/* Información laboral */}
                                <div>
                                    <h4 className="text-lg font-medium text-gray-900 mb-4">
                                        Información Laboral
                                    </h4>
                                    <dl className="space-y-3">
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Número de Empleado
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.numero_empleado}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Área Laboral
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.area_laboral}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">
                                                Registrado
                                            </dt>
                                            <dd className="text-sm text-gray-900">
                                                {guest.created_at}
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Premios de rifa */}
                    {guest.premios_rifa && Array.isArray(guest.premios_rifa) && guest.premios_rifa.length > 0 && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                            <div className="p-6">
                                <h4 className="text-lg font-medium text-gray-900 mb-4">
                                    Categorías de Premios
                                </h4>
                                <div className="flex flex-wrap gap-2">
                                    {guest.premios_rifa.map((categoria, index) => (
                                        <span
                                            key={index}
                                            className="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800"
                                        >
                                            {categoria}
                                        </span>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Estado de asistencia */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">
                                Estado de Asistencia
                            </h4>
                            {guest.has_attended ? (
                                <div className="flex items-center text-green-600">
                                    <CheckCircleIcon className="h-8 w-8 mr-3" />
                                    <div>
                                        <p className="font-medium text-lg">Asistencia Registrada</p>
                                        <p className="text-sm text-gray-600">
                                            Registrado el: {guest.attended_at}
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <div className="flex items-center text-gray-400">
                                    <XCircleIcon className="h-8 w-8 mr-3" />
                                    <div>
                                        <p className="font-medium text-lg">Sin Asistencia</p>
                                        <p className="text-sm text-gray-600">
                                            El invitado aún no ha registrado su asistencia
                                        </p>
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Código QR */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">
                                Código QR
                            </h4>
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 mb-2">
                                        Código único: <span className="font-mono font-medium">{guest.qr_code}</span>
                                    </p>
                                    <p className="text-sm text-gray-500">
                                        Usa este código QR para registrar la asistencia del invitado
                                    </p>
                                </div>
                                <a
                                    href={`/storage/${guest.qr_code_path}`}
                                    download={`QR_${guest.numero_empleado}_${guest.nombre}.png`}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700"
                                >
                                    <QrCodeIcon className="w-4 h-4 mr-2" />
                                    Descargar QR
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}