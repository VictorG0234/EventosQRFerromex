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
        nombre: guest.nombre || '',
        apellido_p: guest.apellido_p || '',
        apellido_m: guest.apellido_m || '',
        numero_empleado: guest.numero_empleado || '',
        area_laboral: guest.area_laboral || '',
        email: guest.email || '',
        premios_rifa: guest.premios_rifa || [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('events.guests.update', [event.id, guest.id]));
    };

    const handlePremiosChange = (categoria) => {
        const updatedPremios = data.premios_rifa.includes(categoria)
            ? data.premios_rifa.filter(p => p !== categoria)
            : [...data.premios_rifa, categoria];
        
        setData('premios_rifa', updatedPremios);
    };

    const categoriasPremios = [
        'Empleados',
        'Gerentes',
        'Directivos',
        'Externos',
        'Proveedores',
        'General'
    ];

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.guests.show', [event.id, guest.id])}
                        className="mr-4 text-gray-600 hover:text-gray-900"
                    >
                        <ArrowLeftIcon className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                            Editar Invitado
                        </h2>
                        <p className="text-sm text-gray-600">
                            {guest.full_name} - {event.name}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Editar ${guest.full_name} - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center mb-6">
                                <div className="flex-shrink-0 mr-4">
                                    <div className="h-12 w-12 bg-blue-100 rounded-full flex items-center justify-center">
                                        <UserIcon className="h-6 w-6 text-blue-600" />
                                    </div>
                                </div>
                                <div>
                                    <h3 className="text-lg font-medium text-gray-900">
                                        Información del Invitado
                                    </h3>
                                    <p className="text-sm text-gray-600">
                                        Actualiza los datos del invitado
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    {/* Información personal */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-medium text-gray-900 border-b pb-2">
                                            Información Personal
                                        </h4>
                                        
                                        <div>
                                            <InputLabel htmlFor="nombre" value="Nombre *" />
                                            <TextInput
                                                id="nombre"
                                                type="text"
                                                name="nombre"
                                                value={data.nombre}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('nombre', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.nombre} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="apellido_p" value="Apellido Paterno *" />
                                            <TextInput
                                                id="apellido_p"
                                                type="text"
                                                name="apellido_p"
                                                value={data.apellido_p}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('apellido_p', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.apellido_p} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="apellido_m" value="Apellido Materno" />
                                            <TextInput
                                                id="apellido_m"
                                                type="text"
                                                name="apellido_m"
                                                value={data.apellido_m}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('apellido_m', e.target.value)}
                                            />
                                            <InputError message={errors.apellido_m} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="email" value="Correo Electrónico" />
                                            <TextInput
                                                id="email"
                                                type="email"
                                                name="email"
                                                value={data.email}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('email', e.target.value)}
                                            />
                                            <InputError message={errors.email} className="mt-2" />
                                        </div>
                                    </div>

                                    {/* Información laboral */}
                                    <div className="space-y-4">
                                        <h4 className="text-md font-medium text-gray-900 border-b pb-2">
                                            Información Laboral
                                        </h4>
                                        
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
                                            <InputLabel htmlFor="area_laboral" value="Área Laboral *" />
                                            <TextInput
                                                id="area_laboral"
                                                type="text"
                                                name="area_laboral"
                                                value={data.area_laboral}
                                                className="mt-1 block w-full"
                                                onChange={(e) => setData('area_laboral', e.target.value)}
                                                required
                                            />
                                            <InputError message={errors.area_laboral} className="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                {/* Categorías de premios */}
                                <div>
                                    <h4 className="text-md font-medium text-gray-900 border-b pb-2 mb-4">
                                        Categorías de Premios
                                    </h4>
                                    <p className="text-sm text-gray-600 mb-4">
                                        Selecciona las categorías de premios a las que puede acceder este invitado
                                    </p>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
                                        {categoriasPremios.map((categoria) => (
                                            <label key={categoria} className="flex items-center">
                                                <input
                                                    type="checkbox"
                                                    checked={data.premios_rifa.includes(categoria)}
                                                    onChange={() => handlePremiosChange(categoria)}
                                                    className="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                />
                                                <span className="ml-2 text-sm text-gray-700">
                                                    {categoria}
                                                </span>
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.premios_rifa} className="mt-2" />
                                </div>

                                <div className="flex items-center justify-end space-x-4 pt-6 border-t">
                                    <Link
                                        href={route('events.guests.show', [event.id, guest.id])}
                                        className="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400"
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