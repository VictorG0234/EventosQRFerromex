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
import axios from 'axios';

export default function Import({ auth, event }) {
    const { data, setData, post, processing, errors } = useForm({
        csv_file: null,
    });

    const [preview, setPreview] = useState(null);
    const [dragActive, setDragActive] = useState(false);
    const [previewLoading, setPreviewLoading] = useState(false);
    const fileInputRef = useRef(null);

    const handleFileSelect = async (file) => {
        if (!file || !file.type.includes('csv')) {
            alert('Por favor selecciona un archivo CSV válido');
            return;
        }

        setData('csv_file', file);
        setPreviewLoading(true);
        
        // Generar preview usando axios (que maneja CSRF automáticamente)
        try {
            const formData = new FormData();
            formData.append('csv_file', file);
            
            const response = await axios.post(route('events.prizes.preview', event.id), formData, {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
            });

            if (response.data.success) {
                setPreview(response.data.data);
            } else {
                alert(response.data.message || 'Error al procesar el archivo');
                setPreview(null);
                setData('csv_file', null);
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || error.message || 'Error al procesar el archivo';
            alert(errorMessage);
            setPreview(null);
            setData('csv_file', null);
        } finally {
            setPreviewLoading(false);
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
        post(route('events.prizes.import.process', event.id));
    };

    const downloadTemplate = () => {
        window.location.href = route('templates.prizes');
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.prizes.index', event.id)}
                        className="mr-4 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                    >
                        ← Volver a premios
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Importar Premios - {event.name}
                    </h2>
                </div>
            }
        >
            <Head title={`Importar Premios - ${event.name}`} />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    
                    {/* Información y template */}
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-start">
                                <InformationCircleIcon className="w-5 h-5 text-blue-500 dark:text-blue-400 mt-0.5 mr-3" />
                                <div className="flex-1">
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Formato del Archivo CSV
                                    </h3>
                                    <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                                        <p className="text-blue-800 dark:text-blue-200 font-medium">
                                            ⚠️ Nota importante: Estos premios son para la rifa pública
                                        </p>
                                    </div>
                                    <p className="text-gray-600 dark:text-gray-300 mb-4">
                                        El archivo debe contener las siguientes columnas en este orden:
                                    </p>
                                    <div className="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                                        <h4 className="font-medium text-gray-900 dark:text-white mb-2">Formato de la Plantilla</h4>
                                        <p className="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                            Cada fila debe incluir la información del premio en el siguiente orden:
                                        </p>
                                        <div className="grid grid-cols-2 md:grid-cols-3 gap-2 mb-2">
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">1. Titulo <span className="text-red-500">*</span></span>
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">2. Descripcion</span>
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">3. Categoria</span>
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">4. Cantidad <span className="text-red-500">*</span></span>
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">5. Valor</span>
                                            <span className="bg-white dark:bg-gray-800 px-2 py-1 rounded text-sm dark:text-gray-200">6. Activo</span>
                                        </div>
                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                            <span className="text-red-500">*</span> Campos requeridos. Categoria es opcional.
                                        </p>
                                    </div>
                                    <div className="flex">
                                        <button
                                            type="button"
                                            onClick={downloadTemplate}
                                            className="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
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
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
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
                                        ) : previewLoading ? (
                                            <div className="space-y-2">
                                                <div className="mx-auto h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                                                <div className="text-sm text-gray-900">
                                                    <span className="font-medium">Procesando archivo...</span>
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
                                                            {Array.isArray(preview.validation_summary?.errors) && preview.validation_summary.errors.slice(0, 5).map((error, index) => (
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
                                                <thead className="bg-gray-50 dark:bg-gray-700">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Fila
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Título
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Descripción
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Categoría
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Cantidad
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Valor
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                                            Estado
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                    {Array.isArray(preview.sample_data) && preview.sample_data.slice(0, 10).map((row, index) => (
                                                        <tr key={index} className={row.valid?.valid ? '' : 'bg-red-50 dark:bg-red-900'}>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                                {row.row_number}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                                {row.mapped?.name || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 text-sm text-gray-900 dark:text-gray-200">
                                                                {row.mapped?.description || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                                {row.mapped?.category || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                                {row.mapped?.stock || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-200">
                                                                {row.mapped?.value ? `$${parseFloat(row.mapped.value).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                {row.valid?.valid ? (
                                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                                        Válido
                                                                    </span>
                                                                ) : (
                                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
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
                                        href={route('events.prizes.index', event.id)}
                                        className="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Cancelar
                                    </Link>

                                    <PrimaryButton 
                                        disabled={processing || !data.csv_file || (preview?.validation_summary?.invalid_rows > 0)}
                                    >
                                        {processing ? 'Importando...' : 'Importar Premios'}
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
