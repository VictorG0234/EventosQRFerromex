import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/Components/ui/alert-dialog';
import { Progress } from '@/Components/ui/progress';
import { Gift, Trophy, Users, Zap, Play, Pause, BarChart3, Sparkles, Target, Clock, CheckCircle, AlertCircle, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function RaffleIndex({ auth, event, prizes, statistics }) {
    const { flash } = usePage().props;
    const [selectedPrize, setSelectedPrize] = useState(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [liveData, setLiveData] = useState({ statistics, recent_winners: [] });
    
    const { data, setData, post, processing } = useForm({
        quantity: 1,
        send_notification: true
    });

    // Auto-refresh live data
    useEffect(() => {
        const interval = setInterval(async () => {
            try {
                const response = await fetch(route('events.raffle.live-data', event.id));
                const data = await response.json();
                setLiveData(data);
            } catch (error) {
                console.error('Failed to fetch live data:', error);
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

    const handleCreateEntries = (prize) => {
        post(route('events.raffle.create-entries', [event.id, prize.id]), {
            onSuccess: () => {
                setSelectedPrize(null);
            },
            onError: (errors) => {
                toast.error('Error al crear participaciones');
            }
        });
    };

    const handleDraw = async (prize) => {
        setIsDrawing(true);
        
        try {
            const response = await fetch(route('events.raffle.draw', [event.id, prize.id]), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    quantity: data.quantity,
                    send_notification: data.send_notification
                })
            });

            const result = await response.json();

            if (result.success) {
                toast.success(result.message);
                // Refresh the page to show updated data
                window.location.reload();
            } else {
                toast.error(result.message);
            }
        } catch (error) {
            toast.error('Error al realizar la rifa');
        } finally {
            setIsDrawing(false);
            setSelectedPrize(null);
        }
    };

    const getPrizeStatusBadge = (prize) => {
        if (!prize.is_available) {
            return <Badge variant="secondary">Agotado</Badge>;
        }
        if (prize.winners_count === 0) {
            return <Badge variant="outline">Sin rifar</Badge>;
        }
        if (prize.remaining_stock === 0) {
            return <Badge variant="destructive">Completo</Badge>;
        }
        return <Badge variant="default">Disponible</Badge>;
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

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Sistema de Rifas - {event.name}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Gestiona y ejecuta rifas de premios para tu evento
                        </p>
                    </div>
                    <div className="flex items-center space-x-2">
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
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center">
                                    <div className="p-2 bg-purple-100 rounded-lg">
                                        <Gift className="w-6 h-6 text-purple-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Total Premios</p>
                                        <p className="text-2xl font-bold text-gray-900">{liveData.statistics.total_prizes}</p>
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
                                        <p className="text-sm font-medium text-gray-600">Ganadores</p>
                                        <p className="text-2xl font-bold text-gray-900">{liveData.statistics.total_winners}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center">
                                    <div className="p-2 bg-blue-100 rounded-lg">
                                        <Users className="w-6 h-6 text-blue-600" />
                                    </div>
                                    <div className="ml-4">
                                        <p className="text-sm font-medium text-gray-600">Participaciones</p>
                                        <p className="text-2xl font-bold text-gray-900">{liveData.statistics.total_entries}</p>
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
                                        <p className="text-sm font-medium text-gray-600">% Premios Entregados</p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {Math.round((liveData.statistics.total_winners / Math.max(liveData.statistics.total_stock, 1)) * 100)}%
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Tabs defaultValue="prizes" className="space-y-6">
                        <TabsList>
                            <TabsTrigger value="prizes">Premios Disponibles</TabsTrigger>
                            <TabsTrigger value="winners">Ganadores Recientes</TabsTrigger>
                            <TabsTrigger value="statistics">Estadísticas</TabsTrigger>
                        </TabsList>

                        <TabsContent value="prizes">
                            {prizes.length === 0 ? (
                                <Card>
                                    <CardContent className="p-8 text-center">
                                        <Gift className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                        <h3 className="text-lg font-medium text-gray-900 mb-2">No hay premios disponibles</h3>
                                        <p className="text-gray-500 mb-4">Crea algunos premios para comenzar con las rifas.</p>
                                        <Link
                                            href={route('events.prizes.create', event.id)}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md"
                                        >
                                            <Gift className="w-4 h-4 mr-2" />
                                            Crear Primer Premio
                                        </Link>
                                    </CardContent>
                                </Card>
                            ) : (
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    {prizes.map((prize) => (
                                        <Card key={prize.id} className="relative overflow-hidden">
                                            <div className="absolute top-4 right-4">
                                                {getPrizeStatusBadge(prize)}
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
                                                    <p className="text-gray-600 text-sm mb-4">{prize.description}</p>
                                                )}

                                                <div className="space-y-3">
                                                    <div className="flex justify-between text-sm">
                                                        <span className="text-gray-600">Stock disponible:</span>
                                                        <span className="font-medium">{prize.remaining_stock} de {prize.stock}</span>
                                                    </div>

                                                    <Progress 
                                                        value={((prize.stock - prize.remaining_stock) / prize.stock) * 100}
                                                        className="h-2"
                                                    />

                                                    <div className="flex justify-between text-sm">
                                                        <span className="text-gray-600">Participantes:</span>
                                                        <span className="font-medium">{prize.eligible_count}</span>
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
                                                </div>

                                                <div className="mt-6 space-y-2">
                                                    <Link
                                                        href={route('events.raffle.show', [event.id, prize.id])}
                                                        className="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                                    >
                                                        <BarChart3 className="w-4 h-4 mr-2" />
                                                        Ver Detalles
                                                    </Link>

                                                    {prize.can_raffle && (
                                                        <div className="flex space-x-2">
                                                            {prize.participants_count === 0 ? (
                                                                <AlertDialog>
                                                                    <AlertDialogTrigger asChild>
                                                                        <Button variant="outline" className="flex-1">
                                                                            <Users className="w-4 h-4 mr-2" />
                                                                            Crear Participaciones
                                                                        </Button>
                                                                    </AlertDialogTrigger>
                                                                    <AlertDialogContent>
                                                                        <AlertDialogHeader>
                                                                            <AlertDialogTitle>Crear Participaciones</AlertDialogTitle>
                                                                            <AlertDialogDescription>
                                                                                Se crearán participaciones automáticamente para todos los invitados elegibles 
                                                                                del premio "{prize.name}". Invitados elegibles: {prize.eligible_count}
                                                                            </AlertDialogDescription>
                                                                        </AlertDialogHeader>
                                                                        <AlertDialogFooter>
                                                                            <Button
                                                                                variant="outline"
                                                                                onClick={() => setSelectedPrize(null)}
                                                                            >
                                                                                Cancelar
                                                                            </Button>
                                                                            <Button
                                                                                onClick={() => handleCreateEntries(prize)}
                                                                                disabled={processing}
                                                                            >
                                                                                {processing ? (
                                                                                    <>
                                                                                        <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                                        Creando...
                                                                                    </>
                                                                                ) : (
                                                                                    <>
                                                                                        <Users className="w-4 h-4 mr-2" />
                                                                                        Crear Participaciones
                                                                                    </>
                                                                                )}
                                                                            </Button>
                                                                        </AlertDialogFooter>
                                                                    </AlertDialogContent>
                                                                </AlertDialog>
                                                            ) : (
                                                                <AlertDialog open={selectedPrize?.id === prize.id} onOpenChange={(open) => !open && setSelectedPrize(null)}>
                                                                    <AlertDialogTrigger asChild>
                                                                        <Button 
                                                                            className="flex-1"
                                                                            onClick={() => setSelectedPrize(prize)}
                                                                            disabled={prize.remaining_stock === 0}
                                                                        >
                                                                            <Sparkles className="w-4 h-4 mr-2" />
                                                                            Realizar Rifa
                                                                        </Button>
                                                                    </AlertDialogTrigger>
                                                                    <AlertDialogContent>
                                                                        <AlertDialogHeader>
                                                                            <AlertDialogTitle>Realizar Rifa - {prize.name}</AlertDialogTitle>
                                                                            <AlertDialogDescription>
                                                                                <div className="space-y-4">
                                                                                    <p>Configura los parámetros de la rifa:</p>
                                                                                    
                                                                                    <div className="space-y-3">
                                                                                        <div>
                                                                                            <label className="block text-sm font-medium mb-2">
                                                                                                Cantidad de ganadores:
                                                                                            </label>
                                                                                            <select
                                                                                                value={data.quantity}
                                                                                                onChange={(e) => setData('quantity', parseInt(e.target.value))}
                                                                                                className="w-full p-2 border border-gray-300 rounded-md"
                                                                                            >
                                                                                                {[...Array(Math.min(prize.remaining_stock, 10))].map((_, i) => (
                                                                                                    <option key={i + 1} value={i + 1}>{i + 1}</option>
                                                                                                ))}
                                                                                            </select>
                                                                                        </div>

                                                                                        <div className="flex items-center space-x-2">
                                                                                            <input
                                                                                                type="checkbox"
                                                                                                id="send_notification"
                                                                                                checked={data.send_notification}
                                                                                                onChange={(e) => setData('send_notification', e.target.checked)}
                                                                                                className="rounded"
                                                                                            />
                                                                                            <label htmlFor="send_notification" className="text-sm">
                                                                                                Enviar notificación por email a ganadores
                                                                                            </label>
                                                                                        </div>
                                                                                    </div>

                                                                                    <div className="bg-blue-50 p-3 rounded-md">
                                                                                        <p className="text-sm text-blue-800">
                                                                                            <strong>Participantes elegibles:</strong> {prize.eligible_count}<br />
                                                                                            <strong>Stock disponible:</strong> {prize.remaining_stock}
                                                                                        </p>
                                                                                    </div>
                                                                                </div>
                                                                            </AlertDialogDescription>
                                                                        </AlertDialogHeader>
                                                                        <AlertDialogFooter>
                                                                            <Button
                                                                                variant="outline"
                                                                                onClick={() => setSelectedPrize(null)}
                                                                                disabled={isDrawing}
                                                                            >
                                                                                Cancelar
                                                                            </Button>
                                                                            <Button
                                                                                onClick={() => handleDraw(prize)}
                                                                                disabled={isDrawing}
                                                                                className="bg-green-600 hover:bg-green-700"
                                                                            >
                                                                                {isDrawing ? (
                                                                                    <>
                                                                                        <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                                        Realizando Rifa...
                                                                                    </>
                                                                                ) : (
                                                                                    <>
                                                                                        <Play className="w-4 h-4 mr-2" />
                                                                                        Realizar Rifa
                                                                                    </>
                                                                                )}
                                                                            </Button>
                                                                        </AlertDialogFooter>
                                                                    </AlertDialogContent>
                                                                </AlertDialog>
                                                            )}
                                                        </div>
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            )}
                        </TabsContent>

                        <TabsContent value="winners">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Trophy className="w-5 h-5 mr-2 text-yellow-500" />
                                        Ganadores Recientes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {liveData.recent_winners.length === 0 ? (
                                        <div className="text-center py-8">
                                            <Trophy className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500">No hay ganadores registrados aún</p>
                                        </div>
                                    ) : (
                                        <div className="space-y-4">
                                            {liveData.recent_winners.map((winner, index) => (
                                                <div key={index} className="flex items-center justify-between p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200">
                                                    <div className="flex items-center">
                                                        <div className="w-8 h-8 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                                            {winner.order}
                                                        </div>
                                                        <div>
                                                            <p className="font-semibold text-gray-900">{winner.guest_name}</p>
                                                            <p className="text-sm text-gray-600">{winner.prize_name}</p>
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <div className="flex items-center text-sm text-gray-500">
                                                            <Clock className="w-4 h-4 mr-1" />
                                                            {winner.drawn_at}
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="statistics">
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Estadísticas Generales</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-4">
                                            <div className="flex justify-between">
                                                <span>Premios activos:</span>
                                                <span className="font-medium">{liveData.statistics.active_prizes}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Stock total:</span>
                                                <span className="font-medium">{liveData.statistics.total_stock}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Stock disponible:</span>
                                                <span className="font-medium">{liveData.statistics.available_stock}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Invitados totales:</span>
                                                <span className="font-medium">{liveData.statistics.total_guests}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Invitados con asistencia:</span>
                                                <span className="font-medium">{liveData.statistics.attended_guests}</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Distribución por Categoría</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {Object.entries(liveData.statistics.prizes_by_category || {}).map(([category, count]) => (
                                                <div key={category} className="flex items-center justify-between">
                                                    <div className="flex items-center">
                                                        <span className="text-lg mr-2">{getPrizeIcon(category)}</span>
                                                        <span className="text-sm">{category}</span>
                                                    </div>
                                                    <Badge variant="outline">{count}</Badge>
                                                </div>
                                            ))}
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}