import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

export default function Create({ auth, event }) {
    const { data, setData, post, processing, errors } = useForm({
        compania: '',
        numero_empleado: '',
        nombre_completo: '',
        correo: '',
        puesto: '',
        nivel_de_puesto: '',
        localidad: '',
        fecha_alta: '',
        descripcion: '',
        categoria_rifa: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('events.guests.store', event.id));
    };

    return (
        <AuthenticatedLayout user={auth.user}>
            <Head title={`Nuevo Invitado - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <Link
                            href={route('events.guests.index', event.id)}
                            className="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 mb-4"
                        >
                            <ArrowLeftIcon className="h-4 w-4 mr-2" />
                            Volver a Invitados
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Nuevo Invitado
                        </h1>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Evento: {event.name}
                        </p>
                    </div>

                    {/* Form */}
                    <div className="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
                        <form onSubmit={submit} className="p-6 space-y-6">
                            {/* Información Básica */}
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Información Básica
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="nombre_completo" value="Nombre Completo *" />
                                        <TextInput
                                            id="nombre_completo"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.nombre_completo}
                                            onChange={(e) => setData('nombre_completo', e.target.value)}
                                            autoFocus
                                        />
                                        <InputError message={errors.nombre_completo} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="correo" value="Correo Electrónico" />
                                        <TextInput
                                            id="correo"
                                            type="email"
                                            className="mt-1 block w-full"
                                            value={data.correo}
                                            onChange={(e) => setData('correo', e.target.value)}
                                        />
                                        <InputError message={errors.correo} className="mt-2" />
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Se enviará un email de bienvenida si se proporciona
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Información Laboral */}
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Información Laboral
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="numero_empleado" value="Número de Empleado *" />
                                        <TextInput
                                            id="numero_empleado"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.numero_empleado}
                                            onChange={(e) => setData('numero_empleado', e.target.value)}
                                        />
                                        <InputError message={errors.numero_empleado} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="compania" value="Compañía *" />
                                        <TextInput
                                            id="compania"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.compania}
                                            onChange={(e) => setData('compania', e.target.value)}
                                        />
                                        <InputError message={errors.compania} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="puesto" value="Puesto *" />
                                        <TextInput
                                            id="puesto"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.puesto}
                                            onChange={(e) => setData('puesto', e.target.value)}
                                        />
                                        <InputError message={errors.puesto} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="nivel_de_puesto" value="Nivel de Puesto" />
                                        <TextInput
                                            id="nivel_de_puesto"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.nivel_de_puesto}
                                            onChange={(e) => setData('nivel_de_puesto', e.target.value)}
                                        />
                                        <InputError message={errors.nivel_de_puesto} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="localidad" value="Localidad *" />
                                        <TextInput
                                            id="localidad"
                                            type="text"
                                            className="mt-1 block w-full"
                                            value={data.localidad}
                                            onChange={(e) => setData('localidad', e.target.value)}
                                        />
                                        <InputError message={errors.localidad} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="fecha_alta" value="Fecha de Alta" />
                                        <TextInput
                                            id="fecha_alta"
                                            type="date"
                                            className="mt-1 block w-full"
                                            value={data.fecha_alta}
                                            onChange={(e) => setData('fecha_alta', e.target.value)}
                                        />
                                        <InputError message={errors.fecha_alta} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Información Adicional */}
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                    Información Adicional
                                </h2>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="md:col-span-1">
                                        <InputLabel htmlFor="descripcion" value="Descripción" />
                                        <select
                                            id="descripcion"
                                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                            value={data.descripcion}
                                            onChange={(e) => setData('descripcion', e.target.value)}
                                        >
                                            <option value="">Selecciona una descripción</option>
                                            <option value="General">General</option>
                                            <option value="Subdirectories">Subdirectories</option>
                                            <option value="Ganadores previos">Ganadores previos</option>
                                            <option value="Nuevo ingreso">Nuevo ingreso</option>
                                            <option value="Directores">Directores</option>
                                            <option value="IMEX">IMEX</option>
                                        </select>
                                        <InputError message={errors.descripcion} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="categoria_rifa" value="Categoría para la Rifa" />
                                        <select
                                            id="categoria_rifa"
                                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-blue-500 dark:focus:border-blue-600 focus:ring-blue-500 dark:focus:ring-blue-600 rounded-md shadow-sm"
                                            value={data.categoria_rifa}
                                            onChange={(e) => setData('categoria_rifa', e.target.value)}
                                        >
                                            <option value="">Selecciona una categoría</option>
                                            <option value="Participa en todo">Participa en todo</option>
                                            <option value="Participa en premios NO en auto">Participa en premios NO en auto</option>
                                            <option value="IMEX">IMEX</option>
                                            <option value="No Participa">No Participa</option>
                                        </select>
                                        <InputError message={errors.categoria_rifa} className="mt-2" />
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Determina el nivel de participación del invitado en rifas
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Nota de campos requeridos */}
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <p className="text-sm text-blue-800 dark:text-blue-200">
                                    <span className="font-semibold">Nota:</span> Los campos marcados con asterisco (*) son obligatorios.
                                    Después de crear el invitado, se generará automáticamente su código QR y se enviará un email de bienvenida si proporcionaste un correo.
                                </p>
                            </div>

                            {/* Botones */}
                            <div className="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Link
                                    href={route('events.guests.index', event.id)}
                                    className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                >
                                    Cancelar
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    {processing ? 'Guardando...' : 'Crear Invitado'}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
