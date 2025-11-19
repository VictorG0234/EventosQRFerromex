import { Head } from '@inertiajs/react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';

export default function PrivacyNotice() {
    return (
        <>
            <Head title="Aviso de Privacidad" />
            
            <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-900 dark:to-gray-800 py-12 px-4">
                <div className="max-w-4xl mx-auto">
                    
                    {/* Botón para cerrar/volver */}
                    <button
                        onClick={() => window.close()}
                        className="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-6"
                    >
                        <ArrowLeftIcon className="h-4 w-4 mr-2" />
                        Cerrar
                    </button>

                    {/* Contenedor principal */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
                        
                        {/* Header */}
                        <div className="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                            <h1 className="text-3xl font-bold text-white text-center">
                                Aviso de Privacidad
                            </h1>
                            <p className="text-blue-100 text-center mt-2">
                                Sistema de Gestión de Eventos
                            </p>
                        </div>

                        {/* Contenido */}
                        <div className="px-8 py-8">
                            
                            {/* 
                                INSTRUCCIONES:
                                Copia y pega aquí el contenido del PDF.
                                Mantén la estructura con los estilos que están abajo.
                                
                                Ejemplo de estructura:
                            */}
                            
                            <div className="prose prose-blue max-w-none dark:prose-invert">
                                
                                {/* Sección de ejemplo - Reemplazar con el contenido real del PDF */}
                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Identidad y Domicilio del Responsable
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                        [AQUÍ VA EL TEXTO DEL PDF - Párrafo 1]
                                    </p>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        [AQUÍ VA EL TEXTO DEL PDF - Párrafo 2]
                                    </p>
                                </section>

                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Datos Personales que se Recaban
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                        [AQUÍ VA EL TEXTO DEL PDF]
                                    </p>
                                    <ul className="list-disc list-inside space-y-2 text-gray-700 dark:text-gray-300">
                                        <li>[Dato personal 1]</li>
                                        <li>[Dato personal 2]</li>
                                        <li>[Dato personal 3]</li>
                                    </ul>
                                </section>

                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Finalidades del Tratamiento
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                        [AQUÍ VA EL TEXTO DEL PDF]
                                    </p>
                                </section>

                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Derechos ARCO
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                                        [AQUÍ VA EL TEXTO DEL PDF]
                                    </p>
                                </section>

                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Transferencia de Datos
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        [AQUÍ VA EL TEXTO DEL PDF]
                                    </p>
                                </section>

                                <section className="mb-8">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Modificaciones al Aviso de Privacidad
                                    </h2>
                                    <p className="text-gray-700 dark:text-gray-300 leading-relaxed">
                                        [AQUÍ VA EL TEXTO DEL PDF]
                                    </p>
                                </section>

                                {/* Información de contacto */}
                                <section className="mt-12 pt-8 border-t-2 border-gray-200 dark:border-gray-700">
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Contacto
                                    </h2>
                                    <div className="bg-blue-50 dark:bg-gray-700 rounded-lg p-6">
                                        <p className="text-gray-700 dark:text-gray-300 mb-3">
                                            Para cualquier duda o aclaración sobre este aviso de privacidad, 
                                            puedes contactarnos en:
                                        </p>
                                        <div className="space-y-2 text-gray-700 dark:text-gray-300">
                                            <p><strong>Email:</strong> privacidad@empresa.com</p>
                                            <p><strong>Teléfono:</strong> +52 (55) 1234-5678</p>
                                            <p><strong>Dirección:</strong> [Dirección completa]</p>
                                        </div>
                                    </div>
                                </section>

                                {/* Fecha de última actualización */}
                                <div className="mt-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                    <p>Última actualización: Noviembre 2025</p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
