import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    ArrowLeftIcon,
    UserIcon
} from '@heroicons/react/24/outline';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';

export default function Edit({ auth, event, guest }) {
    const { data, setData, put, processing, errors } = useForm({
        compania: guest.compania || '',
        numero_empleado: guest.numero_empleado || '',
        nombre_completo: guest.nombre_completo || '',
        correo: guest.correo || '',
        puesto: guest.puesto || '',
        nivel_de_puesto: guest.nivel_de_puesto || '',
        localidad: guest.localidad || '',
        fecha_alta: guest.fecha_alta || '',
        descripcion: guest.descripcion || '',
        categoria_rifa: guest.categoria_rifa || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('events.guests.update', [event.id, guest.id]));
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.guests.show', [event.id, guest.id])}
                        className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Editar Invitado
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                            {guest.full_name} - {event.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Editar ${guest.full_name} - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center mb-6">
                                <div className="flex-shrink-0 mr-4">
                                    <div className="h-12 w-12 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                        <UserIcon className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                    </div>
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                                        Información del Invitado
                                    </h3>
                                    <p className="text-sm text-gray-600 dark:text-gray-300">
                                        Actualiza los datos del invitado
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Información personal */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-medium text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">
                                            Información Personal
                                        </h4>
                                        
                                        <div>
                                            <InputLabel htmlFor="nombre_completo" value="Nombre Completo *" />
                                            <TextInput
                                                id="nombre_completo"
                                                type="text"
                                                name="nombre_completo"
                                                value={data.nombre_completo}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('nombre_completo', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.nombre_completo} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="correo" value="Correo Electrónico" />
                                            <TextInput
                                                id="correo"
                                                type="email"
                                                name="correo"
                                                value={data.correo}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('correo', e.target.value)}
                                            />
                                            <InputError message={errors.correo} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="numero_empleado" value="Número de Empleado *" />
                                            <TextInput
                                                id="numero_empleado"
                                                type="text"
                                                name="numero_empleado"
                                                value={data.numero_empleado}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('numero_empleado', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.numero_empleado} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="fecha_alta" value="Fecha de Alta" />
                                            <TextInput
                                                id="fecha_alta"
                                                type="date"
                                                name="fecha_alta"
                                                value={data.fecha_alta}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('fecha_alta', e.target.value)}
                                            />
                                            <InputError message={errors.fecha_alta} className="mt-2" />
                                        </div>
                                    </div>

                                    {/* Información laboral */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-medium text-gray-900 dark:text-white border-b dark:border-gray-700 pb-2">
                                            Información Laboral
                                        </h4>

                                        <div>
                                            <InputLabel htmlFor="compania" value="Compañía *" />
                                            <TextInput
                                                id="compania"
                                                type="text"
                                                name="compania"
                                                value={data.compania}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('compania', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.compania} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="puesto" value="Puesto *" />
                                            <TextInput
                                                id="puesto"
                                                type="text"
                                                name="puesto"
                                                value={data.puesto}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('puesto', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.puesto} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="nivel_de_puesto" value="Nivel de Puesto" />
                                            <TextInput
                                                id="nivel_de_puesto"
                                                type="text"
                                                name="nivel_de_puesto"
                                                value={data.nivel_de_puesto}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('nivel_de_puesto', e.target.value)}
                                            />
                                            <InputError message={errors.nivel_de_puesto} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="localidad" value="Localidad *" />
                                            <TextInput
                                                id="localidad"
                                                type="text"
                                                name="localidad"
                                                value={data.localidad}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('localidad', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.localidad} className="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                {/* Descripción y categoría de rifa */}
                                <div className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="descripcion" value="Descripción" />
                                        <select
                                            id="descripcion"
                                            name="descripcion"
                                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            value={data.descripcion}
                                            onChange={(e) => setData('descripcion', e.target.value)}
                                        >
                                            <option value="">Selecciona una descripción</option>
                                            <option value="General">General</option>
                                            <option value="Subdirectores">Subdirectores</option>
                                            <option value="IMEX">IMEX</option>
                                            <option value="Directores">Directores</option>
                                            <option value="Nuevo ingreso">Nuevo ingreso</option>
                                            <option value="Ganadores previos">Ganadores previos</option>
                                            <option value="No Participa">No Participa</option>
                                        </select>
                                        <InputError message={errors.descripcion} className="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="categoria_rifa" value="Categoría de Rifa" />
                                        <select
                                            id="categoria_rifa"
                                            name="categoria_rifa"
                                            className="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
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
                                    </div>
                                </div>

                                <div className="flex items-center justify-end space-x-4 pt-6 border-t dark:border-gray-700">
                                    <Link
                                        href={route('events.guests.show', [event.id, guest.id])}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 dark:bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-400 dark:hover:bg-gray-500"
                                    >
                                        Cancelar
                                    </Link>
                                    
                                    <PrimaryButton disabled={processing}>
                                        {processing ? 'Guardando...' : 'Guardar Cambios'}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}