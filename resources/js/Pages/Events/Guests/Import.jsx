import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, useForm } from '@inertiajs/react';
import { 
    DocumentArrowUpIcon, 
    InformationCircleIcon,
    DocumentTextIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon
} from '@heroicons/react/24/outline';
import { useState, useRef } from 'react';

export default function Import({ auth, event }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        csv_file: null,
    });

    const [preview, setPreview] = useState(null);
    const [dragActive, setDragActive] = useState(false);
    const fileInputRef = useRef(null);

    const handleFileSelect = async (file) => {
        if (!file || !file.type.includes('csv')) {
            alert('Por favor selecciona un archivo CSV válido');
            return;
        }

        setData('csv_file', file);
        
        // Generar preview
        try {
            const formData = new FormData();
            formData.append('csv_file', file);
            
            const response = await fetch(route('events.guests.preview', event.id), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (response.ok) {
                const previewData = await response.json();
                setPreview(previewData.data);
            } else {
                const errorData = await response.json();
                alert(errorData.message || 'Error al procesar el archivo');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al procesar el archivo');
        }
    };

    const handleDrag = (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (e.type === "dragenter" || e.type === "dragover") {
            setDragActive(true);
        } else if (e.type === "dragleave") {
            setDragActive(false);
        }
    };

    const handleDrop = (e) => {
        e.preventDefault();
        e.stopPropagation();
        setDragActive(false);
        
        if (e.dataTransfer.files && e.dataTransfer.files[0]) {
            handleFileSelect(e.dataTransfer.files[0]);
        }
    };

    const handleFileInputChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            handleFileSelect(e.target.files[0]);
        }
    };

    const submit = (e) => {
        e.preventDefault();
        if (!data.csv_file) {
            alert('Por favor selecciona un archivo CSV');
            return;
        }
        post(route('events.guests.import.process', event.id));
    };

    const downloadTemplate = () => {
        const csvContent = [
            ['Nombre', 'ApellidoP', 'ApellidoM', 'Correo', 'NumeroEmpleado', 'AreaLaboral', 'PremiosRifa'],
            ['Juan', 'Pérez', 'García', 'juan.perez@empresa.com', 'EMP001', 'Sistemas', 'Categoria1,Categoria2'],
            ['María', 'López', 'Martínez', 'maria.lopez@empresa.com', 'EMP002', 'Recursos Humanos', 'Categoria1,Categoria3'],
            ['Carlos', 'Rodríguez', 'Fernández', 'carlos.rodriguez@empresa.com', 'EMP003', 'Ventas', 'Categoria2,Categoria3']
        ].map(row => row.join(',')).join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'plantilla_invitados.csv';
        link.click();
        window.URL.revokeObjectURL(url);
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.guests.index', event.id)}
                        className="mr-4 text-gray-600 hover:text-gray-900"
                    >
                        ← Volver a invitados
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Importar Invitados - {event.name}
                    </h2>
                </div>
            }
        >
            <Head title={`Importar Invitados - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Información y template */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-start">
                                <InformationCircleIcon className="w-5 h-5 text-blue-500 mt-0.5 mr-3" />
                                <div className="flex-1">
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        Formato del Archivo CSV
                                    </h3>
                                    <p className="text-gray-600 mb-4">
                                        El archivo debe contener las siguientes columnas en este orden:
                                    </p>
                                    <div className="bg-gray-50 p-4 rounded-lg mb-4">
                                        <h4 className="font-medium text-gray-900 mb-2">Formato de la Plantilla</h4>
                                        <p className="text-sm text-gray-600 mb-3">
                                            Cada fila debe incluir la información del invitado en el siguiente orden:
                                        </p>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2 mb-2">
                                            <span className="bg-white px-2 py-1 rounded text-sm">1. Nombre</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">2. ApellidoP</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">3. ApellidoM</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">4. Correo</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">5. NumeroEmpleado</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">6. AreaLaboral</span>
                                            <span className="bg-white px-2 py-1 rounded text-sm">7. PremiosRifa</span>
                                        </div>
                                    </div>
                                    <div className="flex">
                                        <button
                                            type="button"
                                            onClick={downloadTemplate}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                        >
                                            <DocumentTextIcon className="w-4 h-4 mr-2" />
                                            Descargar Plantilla CSV
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Área de carga de archivo */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={submit}>
                                <div
                                    className={`relative border-2 border-dashed rounded-lg p-6 ${
                                        dragActive 
                                            ? 'border-blue-400 bg-blue-50' 
                                            : data.csv_file
                                            ? 'border-green-400 bg-green-50'
                                            : 'border-gray-300'
                                    }`}
                                    onDragEnter={handleDrag}
                                    onDragLeave={handleDrag}
                                    onDragOver={handleDrag}
                                    onDrop={handleDrop}
                                >
                                    <div className="text-center">
                                        {data.csv_file ? (
                                            <div className="space-y-2">
                                                <CheckCircleIcon className="mx-auto h-12 w-12 text-green-400" />
                                                <div className="text-sm text-gray-900">
                                                    <span className="font-medium">Archivo seleccionado:</span>
                                                    <p className="text-gray-600">{data.csv_file.name}</p>
                                                    <p className="text-xs text-gray-500">
                                                        {(data.csv_file.size / 1024).toFixed(1)} KB
                                                    </p>
                                                </div>
                                            </div>
                                        ) : (
                                            <div className="space-y-2">
                                                <DocumentArrowUpIcon className="mx-auto h-12 w-12 text-gray-400" />
                                                <div className="text-sm text-gray-900">
                                                    <span className="font-medium">Arrastra tu archivo CSV aquí</span>
                                                    <p className="text-gray-600">o haz clic para seleccionar</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    
                                    <input
                                        ref={fileInputRef}
                                        type="file"
                                        accept=".csv,.txt"
                                        onChange={handleFileInputChange}
                                        className="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                    />
                                </div>
                                
                                <InputError message={errors.csv_file} className="mt-2" />

                                {/* Vista previa */}
                                {preview && (
                                    <div className="mt-6">
                                        <h4 className="text-lg font-medium text-gray-900 mb-4">
                                            Vista Previa del Archivo
                                        </h4>
                                        
                                        {/* Resumen de validación */}
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                            <div className="bg-blue-50 p-3 rounded-lg">
                                                <div className="text-2xl font-bold text-blue-600">
                                                    {preview.total_rows}
                                                </div>
                                                <div className="text-sm text-blue-700">Total de filas</div>
                                            </div>
                                            <div className="bg-green-50 p-3 rounded-lg">
                                                <div className="text-2xl font-bold text-green-600">
                                                    {preview.validation_summary?.valid_rows || 0}
                                                </div>
                                                <div className="text-sm text-green-700">Filas válidas</div>
                                            </div>
                                            <div className="bg-red-50 p-3 rounded-lg">
                                                <div className="text-2xl font-bold text-red-600">
                                                    {preview.validation_summary?.invalid_rows || 0}
                                                </div>
                                                <div className="text-sm text-red-700">Filas con errores</div>
                                            </div>
                                        </div>

                                        {/* Errores de validación */}
                                        {preview.validation_summary?.invalid_rows > 0 && (
                                            <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                                <div className="flex">
                                                    <ExclamationTriangleIcon className="w-5 h-5 text-red-400 mr-2" />
                                                    <div>
                                                        <h5 className="text-red-800 font-medium">
                                                            Se encontraron errores en el archivo
                                                        </h5>
                                                        <div className="mt-2 text-sm text-red-700">
                                                            {preview.validation_summary.errors?.slice(0, 5).map((error, index) => (
                                                                <div key={index}>
                                                                    Fila {error.row}: {error.errors.join(', ')}
                                                                </div>
                                                            ))}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        )}

                                        {/* Muestra de datos */}
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Fila
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Nombre
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Apellidos
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Empleado
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Área
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Estado
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {preview.sample_data?.slice(0, 10).map((row, index) => (
                                                        <tr key={index} className={row.valid?.valid ? '' : 'bg-red-50'}>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {row.row_number}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {row.mapped?.nombre || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {`${row.mapped?.apellido_p || ''} ${row.mapped?.apellido_m || ''}`.trim() || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {row.mapped?.numero_empleado || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {row.mapped?.area_laboral || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                {row.valid?.valid ? (
                                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                        Válido
                                                                    </span>
                                                                ) : (
                                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                        Error
                                                                    </span>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                )}

                                {/* Botones */}
                                <div className="flex items-center justify-end space-x-3 pt-6 border-t mt-6">
                                    <Link
                                        href={route('events.guests.index', event.id)}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Cancelar
                                    </Link>

                                    <PrimaryButton 
                                        disabled={processing || !data.csv_file || (preview?.validation_summary?.invalid_rows > 0)}
                                    >
                                        {processing ? 'Importando...' : 'Importar Invitados'}
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