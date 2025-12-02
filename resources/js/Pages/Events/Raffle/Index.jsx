import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/Components/ui/badge';
import { Gift, Trophy, Sparkles, Target, ArrowLeft, Users } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function RaffleIndex({ auth, event, prizes, statistics, public_raffle_status, public_raffle_completed, public_raffle_total, general_raffle_status, general_winners_count, total_prizes }) {
    const { flash } = usePage().props;
    const [liveData, setLiveData] = useState({ statistics, recent_winners: [] });

    // Auto-refresh live data
    useEffect(() => {
        const interval = setInterval(async () => {
            try {
                const response = await fetch(route('events.raffle.live-data', event.id));
                const data = await response.json();
                setLiveData(data);
            } catch (error) {
                // Error al obtener datos en vivo
            }
        }, 5000);

        return () => clearInterval(interval);
    }, [event.id]);

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);


    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center">
                        <Link
                            href={route('events.show', event.id)}
                            className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Sistema de Rifas - {event.name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                Gestiona y ejecuta rifas de premios para tu evento
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('events.draw.cards', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Sparkles className="w-4 h-4 mr-2" />
                            Ir a Rifa Pública
                        </Link>
                        <Link
                            href={route('events.draw.general', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Trophy className="w-4 h-4 mr-2" />
                            Ir a Rifa General
                        </Link>
                        <Link
                            href={route('events.prizes.index', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 text-sm font-medium rounded-md transition-colors"
                        >
                            <Gift className="w-4 h-4 mr-2" />
                            Gestionar Premios
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Rifas - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Overview */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Gift className="h-8 w-8 text-purple-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">{liveData.statistics.total_prizes}</div>
                                        <div className="text-sm text-gray-600">Total Premios</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Trophy className="h-8 w-8 text-yellow-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">{liveData.statistics.total_winners}</div>
                                        <div className="text-sm text-gray-600">Ganadores</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Users className="h-8 w-8 text-blue-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">{liveData.statistics.total_entries}</div>
                                        <div className="text-sm text-gray-600">Participaciones</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Target className="h-8 w-8 text-orange-500" />
                                    </div>
                                    <div className="ml-4">
                                        <div className="text-2xl font-bold text-gray-900">
                                            {liveData.statistics.delivered_percentage || 0}%
                                        </div>
                                        <div className="text-sm text-gray-600">% Premios Entregados</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Tres secciones principales */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {/* Rifa Pública */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-purple-200">
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex items-center">
                                        <div className="bg-purple-100 rounded-full p-3 mr-3">
                                            <Sparkles className="w-6 h-6 text-purple-600" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900">Rifa Pública</h3>
                                            <p className="text-sm text-gray-500">Rifas individuales</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="mb-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm text-gray-600">Estado:</span>
                                        <Badge 
                                            className={
                                                public_raffle_status === 'completa' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : public_raffle_status === 'en_progreso'
                                                    ? 'bg-yellow-100 text-yellow-800'
                                                    : 'bg-gray-100 text-gray-800'
                                            }
                                        >
                                            {public_raffle_status === 'completa' 
                                                ? 'Completa' 
                                                : public_raffle_status === 'en_progreso'
                                                ? 'En Progreso'
                                                : 'Pendiente'}
                                        </Badge>
                                    </div>
                                    {public_raffle_total > 0
                                        ? <div className="text-sm text-gray-600">
                                            <span className="font-medium">{public_raffle_completed}</span> de <span className="font-medium">{public_raffle_total}</span> premios rifados
                                        </div>
                                        : <div className="text-sm text-gray-600">
                                            No hay premios rifados
                                        </div>
                                    }
                                </div>

                                <Link
                                    href={route('events.draw.cards', event.id)}
                                    className="w-full inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    <Sparkles className="w-4 h-4 mr-2" />
                                    Ir a Rifa Pública
                                </Link>
                            </div>
                        </div>

                        {/* Rifa General */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-orange-200">
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex items-center">
                                        <div className="bg-orange-100 rounded-full p-3 mr-3">
                                            <Trophy className="w-6 h-6 text-orange-600" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900">Rifa General</h3>
                                            <p className="text-sm text-gray-500">Selecciona ganadores</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="mb-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm text-gray-600">Estado:</span>
                                        <Badge 
                                            className={
                                                general_raffle_status === 'completa' 
                                                    ? 'bg-green-100 text-green-800' 
                                                    : 'bg-gray-100 text-gray-800'
                                            }
                                        >
                                            {general_raffle_status === 'completa' ? 'Completa' : 'Pendiente'}
                                        </Badge>
                                    </div>
                                    {
                                        <div className="text-sm text-gray-600">
                                            <span className="font-medium">{general_winners_count}</span> ganadores seleccionados
                                        </div>
                                    }
                                </div>

                                <Link
                                    href={route('events.draw.general', event.id)}
                                    className="w-full inline-flex items-center justify-center px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    <Trophy className="w-4 h-4 mr-2" />
                                    Ir a Rifa General
                                </Link>
                            </div>
                        </div>

                        {/* Gestionar Premios */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-blue-200">
                            <div className="p-6">
                                <div className="flex items-center justify-between mb-4">
                                    <div className="flex items-center">
                                        <div className="bg-blue-100 rounded-full p-3 mr-3">
                                            <Gift className="w-6 h-6 text-blue-600" />
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-semibold text-gray-900">Gestionar Premios</h3>
                                            <p className="text-sm text-gray-500">Administración</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="mb-4">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="text-sm text-gray-600">Estado:</span>
                                        <Badge className="bg-blue-100 text-blue-800">
                                            {total_prizes} premios
                                        </Badge>
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        <span className="font-medium">{liveData.statistics?.active_prizes || 0}</span> activos
                                    </div>
                                </div>

                                <Link
                                    href={route('events.prizes.index', event.id)}
                                    className="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    <Gift className="w-4 h-4 mr-2" />
                                    Gestionar Premios
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
