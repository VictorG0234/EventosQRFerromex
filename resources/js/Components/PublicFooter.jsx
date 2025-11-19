import { Link } from '@inertiajs/react';

export default function PublicFooter() {
    const currentYear = new Date().getFullYear();
    
    return (
        <footer className="mt-auto py-6 px-4 border-t border-gray-200 dark:border-gray-700 bg-white/50 dark:bg-gray-800/50 backdrop-blur-sm">
            <div className="max-w-7xl mx-auto">
                <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                    
                    {/* Información de la empresa */}
                    <div className="text-center md:text-left">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            © {currentYear} Ferromex. Todos los derechos reservados.
                        </p>
                    </div>
                    
                    {/* Enlaces legales */}
                    <div className="flex flex-wrap justify-center gap-4 md:gap-6">
                        <a
                            href={route('public.privacy.notice')}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 
                                     transition-colors duration-200 flex items-center gap-1"
                        >
                            Aviso de Privacidad
                            <svg className="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} 
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                        
                        {/* Puedes agregar más enlaces aquí si los necesitas */}
                        {/* 
                        <a
                            href="/terminos-y-condiciones"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Términos y Condiciones
                        </a>
                        */}
                    </div>
                    
                    {/* Información adicional */}
                    <div className="text-center md:text-right">
                        <p className="text-xs text-gray-500 dark:text-gray-500">
                            Sistema de Gestión de Eventos
                        </p>
                    </div>
                    
                </div>
            </div>
        </footer>
    );
}
