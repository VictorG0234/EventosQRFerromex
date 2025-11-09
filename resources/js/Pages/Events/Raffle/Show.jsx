import React, { useState, useEffect } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/Components/ui/alert-dialog';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/Components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Progress } from '@/Components/ui/progress';
import { ArrowLeft, Gift, Trophy, Users, Play, UserCheck, Mail, Clock, CheckCircle, XCircle, RotateCcw, Trash2, Crown, AlertTriangle, Sparkles, RefreshCw, Eye } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function RaffleShow({ auth, event, prize, eligible_guests, validation, results }) {
    const { flash } = usePage().props;
    const [entries, setEntries] = useState([]);
    const [loadingEntries, setLoadingEntries] = useState(false);
    const [selectedWinner, setSelectedWinner] = useState('');
    const [isDrawing, setIsDrawing] = useState(false);
    
    const { data, setData, post, processing } = useForm({
        quantity: 1,
        send_notification: true
    });

    const manualWinnerForm = useForm({
        guest_id: '',
        send_notification: true
    });

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const loadEntries = async () => {
        setLoadingEntries(true);
        try {
            const response = await fetch(route('events.raffle.entries', [event.id, prize.id]));
            const data = await response.json();
            setEntries(data.entries);
        } catch (error) {
            toast.error('Error al cargar las participaciones');
        } finally {
            setLoadingEntries(false);
        }
    };

    useEffect(() => {
        loadEntries();
    }, []);

    const handleCreateEntries = () => {
        post(route('events.raffle.create-entries', [event.id, prize.id]), {
            onSuccess: () => {
                loadEntries();
            },
            onError: () => {
                toast.error('Error al crear las participaciones');
            }
        });
    };

    const handleDraw = async () => {
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
                loadEntries();
            } else {
                toast.error(result.message);
            }
        } catch (error) {
            toast.error('Error al realizar la rifa');
        } finally {
            setIsDrawing(false);
        }
    };

    const handleSelectWinner = () => {
        manualWinnerForm.post(route('events.raffle.select-winner', [event.id, prize.id]), {
            onSuccess: () => {
                manualWinnerForm.reset();
                setSelectedWinner('');
                loadEntries();
            },
            onError: () => {
                toast.error('Error al seleccionar ganador');
            }
        });
    };

    const handleCancelRaffle = () => {
        post(route('events.raffle.cancel', [event.id, prize.id]), {
            onSuccess: () => {
                loadEntries();
            },
            onError: () => {
                toast.error('Error al cancelar la rifa');
            }
        });
    };

    const getStatusBadge = (status) => {
        const badges = {
            won: <Badge className="bg-green-100 text-green-800">Ganador</Badge>,
            pending: <Badge variant="outline">Pendiente</Badge>,
            cancelled: <Badge variant="secondary">Cancelado</Badge>
        };
        return badges[status] || <Badge variant="outline">Desconocido</Badge>;
    };

    const getValidationIcon = (canRaffle) => {
        return canRaffle ? 
            <CheckCircle className="w-5 h-5 text-green-500" /> : 
            <XCircle className="w-5 h-5 text-red-500" />;
    };

    const eligibleGuestsForManualSelection = eligible_guests.filter(guest => 
        !entries.some(entry => entry.guest.id === guest.id && entry.status === 'won')
    );

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex justify-between items-center">
                    <div className="flex items-center">
                        <Link
                            href={route('events.raffle.index', event.id)}
                            className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        >
                            <ArrowLeft className="w-5 h-5" />
                        </Link>
                        <div>
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Rifa: {prize.name}
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                {prize.category} • Stock: {prize.stock} • Disponible: {prize.stock - results.winners_count}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Button 
                            variant="outline" 
                            onClick={loadEntries}
                            disabled={loadingEntries}
                        >
                            <RefreshCw className={`w-4 h-4 mr-2 ${loadingEntries ? 'animate-spin' : ''}`} />
                            Actualizar
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Rifa: ${prize.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Prize Overview */}
                    <Card className="mb-8">
                        <CardContent className="p-6">
                            <div className="flex items-start space-x-6">
                                {prize.image && (
                                    <div className="w-32 h-32 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0">
                                        <img 
                                            src={`/storage/${prize.image}`} 
                                            alt={prize.name}
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                )}
                                
                                <div className="flex-1">
                                    <div className="flex items-center mb-2">
                                        <h3 className="text-2xl font-bold text-gray-900 mr-4">{prize.name}</h3>
                                        <Badge className="bg-blue-100 text-blue-800">{prize.category}</Badge>
                                    </div>
                                    
                                    {prize.description && (
                                        <p className="text-gray-600 mb-4">{prize.description}</p>
                                    )}

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <p className="text-sm text-gray-600">Stock Total</p>
                                            <p className="text-lg font-semibold">{prize.stock}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Disponible</p>
                                            <p className="text-lg font-semibold text-green-600">{prize.stock - results.winners_count}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-600">Ganadores</p>
                                            <p className="text-lg font-semibold text-blue-600">{results.winners_count}</p>
                                        </div>
                                    </div>

                                    <div className="mt-4">
                                        <div className="flex justify-between text-sm mb-1">
                                            <span>Progreso de la rifa</span>
                                            <span>{Math.round((results.winners_count / prize.stock) * 100)}%</span>
                                        </div>
                                        <Progress value={(results.winners_count / prize.stock) * 100} className="h-2" />
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Validation Status */}
                    <Card className="mb-8">
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                {getValidationIcon(validation.can_raffle)}
                                <span className="ml-2">Estado de la Rifa</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2">
                                {validation.messages.map((message, index) => (
                                    <div key={index} className={`p-3 rounded-md ${validation.can_raffle ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'}`}>
                                        {message}
                                    </div>
                                ))}
                            </div>

                            {validation.can_raffle && (
                                <div className="mt-6 flex space-x-4">
                                    {results.total_entries === 0 ? (
                                        <AlertDialog>
                                            <AlertDialogTrigger asChild>
                                                <Button>
                                                    <Users className="w-4 h-4 mr-2" />
                                                    Crear Participaciones ({eligible_guests.length})
                                                </Button>
                                            </AlertDialogTrigger>
                                            <AlertDialogContent>
                                                <AlertDialogHeader>
                                                    <AlertDialogTitle>Crear Participaciones</AlertDialogTitle>
                                                    <AlertDialogDescription>
                                                        Se crearán {eligible_guests.length} participaciones automáticamente para todos los invitados elegibles.
                                                    </AlertDialogDescription>
                                                </AlertDialogHeader>
                                                <AlertDialogFooter>
                                                    <Button variant="outline">Cancelar</Button>
                                                    <Button onClick={handleCreateEntries} disabled={processing}>
                                                        {processing ? (
                                                            <>
                                                                <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                Creando...
                                                            </>
                                                        ) : (
                                                            'Crear Participaciones'
                                                        )}
                                                    </Button>
                                                </AlertDialogFooter>
                                            </AlertDialogContent>
                                        </AlertDialog>
                                    ) : (
                                        <div className="flex space-x-2">
                                            <AlertDialog>
                                                <AlertDialogTrigger asChild>
                                                    <Button disabled={results.pending_entries === 0}>
                                                        <Sparkles className="w-4 h-4 mr-2" />
                                                        Realizar Rifa Automática
                                                    </Button>
                                                </AlertDialogTrigger>
                                                <AlertDialogContent>
                                                    <AlertDialogHeader>
                                                        <AlertDialogTitle>Realizar Rifa Automática</AlertDialogTitle>
                                                        <AlertDialogDescription>
                                                            <div className="space-y-4">
                                                                <div>
                                                                    <label className="block text-sm font-medium mb-2">
                                                                        Cantidad de ganadores:
                                                                    </label>
                                                                    <select
                                                                        value={data.quantity}
                                                                        onChange={(e) => setData('quantity', parseInt(e.target.value))}
                                                                        className="w-full p-2 border border-gray-300 rounded-md"
                                                                    >
                                                                        {[...Array(Math.min(prize.stock - results.winners_count, results.pending_entries, 10))].map((_, i) => (
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
                                                                        Enviar notificación por email
                                                                    </label>
                                                                </div>

                                                                <div className="bg-blue-50 p-3 rounded-md">
                                                                    <p className="text-sm text-blue-800">
                                                                        <strong>Participaciones pendientes:</strong> {results.pending_entries}<br />
                                                                        <strong>Stock disponible:</strong> {prize.stock - results.winners_count}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </AlertDialogDescription>
                                                    </AlertDialogHeader>
                                                    <AlertDialogFooter>
                                                        <Button variant="outline" disabled={isDrawing}>
                                                            Cancelar
                                                        </Button>
                                                        <Button onClick={handleDraw} disabled={isDrawing} className="bg-green-600 hover:bg-green-700">
                                                            {isDrawing ? (
                                                                <>
                                                                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                    Realizando...
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

                                            <Dialog>
                                                <DialogTrigger asChild>
                                                    <Button variant="outline" disabled={eligibleGuestsForManualSelection.length === 0}>
                                                        <Crown className="w-4 h-4 mr-2" />
                                                        Selección Manual
                                                    </Button>
                                                </DialogTrigger>
                                                <DialogContent>
                                                    <DialogHeader>
                                                        <DialogTitle>Selección Manual de Ganador</DialogTitle>
                                                        <DialogDescription>
                                                            Elige manualmente quién será el ganador del premio.
                                                        </DialogDescription>
                                                    </DialogHeader>
                                                    <div className="space-y-4">
                                                        <div>
                                                            <label className="block text-sm font-medium mb-2">
                                                                Seleccionar ganador:
                                                            </label>
                                                            <select
                                                                value={manualWinnerForm.data.guest_id}
                                                                onChange={(e) => manualWinnerForm.setData('guest_id', e.target.value)}
                                                                className="w-full p-2 border border-gray-300 rounded-md"
                                                            >
                                                                <option value="">Seleccionar invitado...</option>
                                                                {eligibleGuestsForManualSelection.map((guest) => (
                                                                    <option key={guest.id} value={guest.id}>
                                                                        {guest.name} ({guest.employee_number})
                                                                    </option>
                                                                ))}
                                                            </select>
                                                        </div>

                                                        <div className="flex items-center space-x-2">
                                                            <input
                                                                type="checkbox"
                                                                id="manual_send_notification"
                                                                checked={manualWinnerForm.data.send_notification}
                                                                onChange={(e) => manualWinnerForm.setData('send_notification', e.target.checked)}
                                                                className="rounded"
                                                            />
                                                            <label htmlFor="manual_send_notification" className="text-sm">
                                                                Enviar notificación por email
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <DialogFooter>
                                                        <Button variant="outline">Cancelar</Button>
                                                        <Button 
                                                            onClick={handleSelectWinner} 
                                                            disabled={!manualWinnerForm.data.guest_id || manualWinnerForm.processing}
                                                            className="bg-purple-600 hover:bg-purple-700"
                                                        >
                                                            {manualWinnerForm.processing ? (
                                                                <>
                                                                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                    Seleccionando...
                                                                </>
                                                            ) : (
                                                                <>
                                                                    <Crown className="w-4 h-4 mr-2" />
                                                                    Seleccionar Ganador
                                                                </>
                                                            )}
                                                        </Button>
                                                    </DialogFooter>
                                                </DialogContent>
                                            </Dialog>

                                            {results.total_entries > 0 && (
                                                <AlertDialog>
                                                    <AlertDialogTrigger asChild>
                                                        <Button variant="destructive">
                                                            <XCircle className="w-4 h-4 mr-2" />
                                                            Cancelar Rifa
                                                        </Button>
                                                    </AlertDialogTrigger>
                                                    <AlertDialogContent>
                                                        <AlertDialogHeader>
                                                            <AlertDialogTitle>¿Cancelar Rifa?</AlertDialogTitle>
                                                            <AlertDialogDescription>
                                                                Esta acción marcará todas las participaciones como canceladas. 
                                                                Los ganadores actuales perderán su estado de ganador.
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter>
                                                            <Button variant="outline">No, mantener</Button>
                                                            <Button 
                                                                variant="destructive" 
                                                                onClick={handleCancelRaffle}
                                                                disabled={processing}
                                                            >
                                                                Sí, cancelar rifa
                                                            </Button>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Entries and Results */}
                    <Tabs defaultValue="entries" className="space-y-6">
                        <TabsList>
                            <TabsTrigger value="entries">
                                Participaciones ({entries.length})
                            </TabsTrigger>
                            <TabsTrigger value="winners">
                                Ganadores ({entries.filter(e => e.status === 'won').length})
                            </TabsTrigger>
                            <TabsTrigger value="eligible">
                                Invitados Elegibles ({eligible_guests.length})
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="entries">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between">
                                        <span>Todas las Participaciones</span>
                                        <Badge variant="outline">
                                            {entries.length} total
                                        </Badge>
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {loadingEntries ? (
                                        <div className="text-center py-8">
                                            <RefreshCw className="w-8 h-8 text-gray-400 mx-auto animate-spin mb-4" />
                                            <p className="text-gray-500">Cargando participaciones...</p>
                                        </div>
                                    ) : entries.length === 0 ? (
                                        <div className="text-center py-8">
                                            <Users className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500">No hay participaciones registradas</p>
                                        </div>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Invitado
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Estado
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Fecha Rifa
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Orden
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Creado
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {entries.map((entry) => (
                                                        <tr key={entry.id} className={entry.status === 'won' ? 'bg-green-50' : ''}>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                <div>
                                                                    <div className="font-medium text-gray-900">
                                                                        {entry.guest.name}
                                                                    </div>
                                                                    <div className="text-sm text-gray-500">
                                                                        {entry.guest.employee_number} • {entry.guest.work_area}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                {getStatusBadge(entry.status)}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {entry.drawn_at || '-'}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                {entry.order && (
                                                                    <Badge className="bg-yellow-100 text-yellow-800">
                                                                        #{entry.order}
                                                                    </Badge>
                                                                )}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {entry.created_at}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="winners">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center">
                                        <Trophy className="w-5 h-5 mr-2 text-yellow-500" />
                                        Lista de Ganadores
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {entries.filter(e => e.status === 'won').length === 0 ? (
                                        <div className="text-center py-8">
                                            <Trophy className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500">No hay ganadores aún</p>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {entries
                                                .filter(entry => entry.status === 'won')
                                                .sort((a, b) => a.order - b.order)
                                                .map((entry) => (
                                                    <div key={entry.id} className="p-4 bg-gradient-to-r from-yellow-50 to-orange-50 rounded-lg border border-yellow-200">
                                                        <div className="flex items-center justify-between mb-2">
                                                            <div className="flex items-center">
                                                                <div className="w-8 h-8 bg-yellow-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3">
                                                                    {entry.order}
                                                                </div>
                                                                <div>
                                                                    <p className="font-semibold text-gray-900">{entry.guest.name}</p>
                                                                    <p className="text-sm text-gray-600">{entry.guest.employee_number}</p>
                                                                </div>
                                                            </div>
                                                            <Trophy className="w-6 h-6 text-yellow-500" />
                                                        </div>
                                                        <div className="flex items-center text-sm text-gray-500">
                                                            <Clock className="w-4 h-4 mr-1" />
                                                            {entry.drawn_at}
                                                        </div>
                                                        <div className="flex items-center text-sm text-gray-500 mt-1">
                                                            <Mail className="w-4 h-4 mr-1" />
                                                            {entry.guest.email}
                                                        </div>
                                                    </div>
                                                ))}
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="eligible">
                            <Card>
                                <CardHeader>
                                    <CardTitle>Invitados Elegibles</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {eligible_guests.length === 0 ? (
                                        <div className="text-center py-8">
                                            <Users className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                            <p className="text-gray-500">No hay invitados elegibles para este premio</p>
                                        </div>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200">
                                                <thead className="bg-gray-50">
                                                    <tr>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Invitado
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Email
                                                        </th>
                                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            Asistencia
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody className="bg-white divide-y divide-gray-200">
                                                    {eligible_guests.map((guest) => (
                                                        <tr key={guest.id}>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                <div>
                                                                    <div className="font-medium text-gray-900">{guest.name}</div>
                                                                    <div className="text-sm text-gray-500">
                                                                        {guest.employee_number} • {guest.work_area}
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {guest.email}
                                                            </td>
                                                            <td className="px-6 py-4 whitespace-nowrap">
                                                                <div className="flex items-center">
                                                                    <CheckCircle className="w-4 h-4 text-green-500 mr-1" />
                                                                    <span className="text-sm text-gray-600">{guest.attended_at}</span>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}