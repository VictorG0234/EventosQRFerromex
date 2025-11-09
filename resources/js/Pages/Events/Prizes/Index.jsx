import React, { useEffect } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/Components/ui/alert-dialog';
import { Progress } from '@/Components/ui/progress';
import { Gift, Plus, Edit, Trash2, Eye, Power, Users, Trophy, Target, BarChart3, Sparkles } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function PrizesIndex({ auth, event, prizes, statistics }) {
    const { flash } = usePage().props;
    const { delete: destroy, processing } = useForm();

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleDelete = (prize) => {
        destroy(route('events.prizes.destroy', [event.id, prize.id]));
    };

    const handleToggleActive = (prize) => {
        const form = useForm();
        form.patch(route('events.prizes.toggle-active', [event.id, prize.id]));
    };

    const getPrizeIcon = (category) => {
        const icons = {
            'Electrónicos': '📱',
            'Hogar': '🏠',
            'Viajes': '✈️',
            'Deportes': '⚽',
            'Cultura': '📚',
            'Gastronómicos': '🍽️'
        };
        return icons[category] || '🎁';
    };

    const getStatusBadge = (prize) => {
        if (!prize.active) {
            return <Badge variant="secondary">Inactivo</Badge>;
        }
        if (!prize.is_available) {
            return <Badge variant="destructive">Agotado</Badge>;
        }
        if (prize.winners_count === 0) {
            return <Badge variant="outline">Sin rifar</Badge>;
        }
        if (prize.stock_percentage === 100) {
            return <Badge className="bg-red-100 text-red-800">Completo</Badge>;
        }
        return <Badge className="bg-green-100 text-green-800">Disponible</Badge>;
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Premios - {event.name}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Gestiona los premios disponibles para rifas
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <a
                            href={route('templates.prizes')}
                            className="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Gift className="w-4 h-4 mr-2" />
                            Descargar Plantilla
                        </a>
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 dark:bg-purple-500 dark:hover:bg-purple-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Sparkles className="w-4 h-4 mr-2" />
                            Ir a Rifas
                        </Link>
                        <Link
                            href={route('events.prizes.create', event.id)}
                            className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-md transition-colors"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Nuevo Premio
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Premios - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Statistics Overview */}
                    {statistics && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <Card>
                                <CardContent className="p-6">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <Gift className="w-6 h-6 text-blue-600" />
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Total Premios</p>
                                            <p className="text-2xl font-bold text-gray-900">{statistics.total_prizes}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-green-100 rounded-lg">
                                            <Trophy className="w-6 h-6 text-green-600" />
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Stock Total</p>
                                            <p className="text-2xl font-bold text-gray-900">{statistics.total_stock}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-purple-100 rounded-lg">
                                            <Users className="w-6 h-6 text-purple-600" />
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Participaciones</p>
                                            <p className="text-2xl font-bold text-gray-900">{statistics.total_entries}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardContent className="p-6">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-orange-100 rounded-lg">
                                            <Target className="w-6 h-6 text-orange-600" />
                                        </div>
                                        <div className="ml-4">
                                            <p className="text-sm font-medium text-gray-600">Ganadores</p>
                                            <p className="text-2xl font-bold text-gray-900">{statistics.total_winners}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    )}

                    {/* Prizes List */}
                    {prizes.length === 0 ? (
                        <Card>
                            <CardContent className="p-8 text-center">
                                <Gift className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">No hay premios creados</h3>
                                <p className="text-gray-500 mb-6">
                                    Crea el primer premio para comenzar con el sistema de rifas.
                                </p>
                                <Link
                                    href={route('events.prizes.create', event.id)}
                                    className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Crear Primer Premio
                                </Link>
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {prizes.map((prize) => (
                                <Card key={prize.id} className="relative overflow-hidden">
                                    <div className="absolute top-4 right-4 z-10">
                                        {getStatusBadge(prize)}
                                    </div>
                                    
                                    {prize.image && (
                                        <div className="h-48 bg-gray-100 overflow-hidden">
                                            <img 
                                                src={`/storage/${prize.image}`} 
                                                alt={prize.name}
                                                className="w-full h-full object-cover"
                                            />
                                        </div>
                                    )}
                                    
                                    <CardContent className="p-6">
                                        <div className="flex items-start mb-3">
                                            <span className="text-2xl mr-3">{getPrizeIcon(prize.category)}</span>
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-lg text-gray-900 mb-1">{prize.name}</h3>
                                                <p className="text-sm text-gray-500">{prize.category}</p>
                                            </div>
                                        </div>

                                        {prize.description && (
                                            <p className="text-gray-600 text-sm mb-4 line-clamp-2">{prize.description}</p>
                                        )}

                                        <div className="space-y-3">
                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Stock:</span>
                                                <span className="font-medium">{prize.stock - prize.winners_count} / {prize.stock}</span>
                                            </div>

                                            <Progress 
                                                value={prize.stock_percentage}
                                                className="h-2"
                                            />

                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Participantes:</span>
                                                <span className="font-medium">{prize.participants_count}</span>
                                            </div>

                                            <div className="flex justify-between text-sm">
                                                <span className="text-gray-600">Ganadores:</span>
                                                <span className="font-medium text-green-600">{prize.winners_count}</span>
                                            </div>

                                            {prize.value && (
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Valor:</span>
                                                    <span className="font-medium">${prize.value}</span>
                                                </div>
                                            )}

                                            <div className="text-xs text-gray-500">
                                                Creado: {prize.created_at}
                                            </div>
                                        </div>

                                        <div className="mt-6 flex flex-wrap gap-2">
                                            <Link
                                                href={route('events.prizes.show', [event.id, prize.id])}
                                                className="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition-colors"
                                            >
                                                <Eye className="w-3 h-3 mr-1" />
                                                Ver
                                            </Link>
                                            
                                            <Link
                                                href={route('events.prizes.edit', [event.id, prize.id])}
                                                className="inline-flex items-center px-3 py-1.5 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs font-medium rounded transition-colors"
                                            >
                                                <Edit className="w-3 h-3 mr-1" />
                                                Editar
                                            </Link>

                                            <button
                                                onClick={() => handleToggleActive(prize)}
                                                className={`inline-flex items-center px-3 py-1.5 text-xs font-medium rounded transition-colors ${
                                                    prize.active 
                                                        ? 'bg-orange-100 hover:bg-orange-200 text-orange-700'
                                                        : 'bg-green-100 hover:bg-green-200 text-green-700'
                                                }`}
                                            >
                                                <Power className="w-3 h-3 mr-1" />
                                                {prize.active ? 'Desactivar' : 'Activar'}
                                            </button>

                                            <Link
                                                href={route('events.raffle.show', [event.id, prize.id])}
                                                className="inline-flex items-center px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-700 text-xs font-medium rounded transition-colors"
                                            >
                                                <BarChart3 className="w-3 h-3 mr-1" />
                                                Rifa
                                            </Link>

                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <button className="inline-flex items-center px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 text-xs font-medium rounded transition-colors">
                                                        <Trash2 className="w-3 h-3 mr-1" />
                                                        Eliminar
                                                    </button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>¿Eliminar premio?</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            Esta acción eliminará permanentemente el premio "{prize.name}".
                                                            {prize.participants_count > 0 && (
                                                                <span className="text-red-600 block mt-2">
                                                                    ¡Advertencia! Este premio tiene {prize.participants_count} participaciones registradas.
                                                                </span>
                                                            )}
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <Button variant="outline">Cancelar</Button>
                                                        <Button 
                                                            variant="destructive" 
                                                            onClick={() => handleDelete(prize)}
                                                            disabled={processing}
                                                        >
                                                            Eliminar
                                                        </Button>
                                                    </AlertDialogFooter>
                                                </AlertDialogContent>
                                            </AlertDialog>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}