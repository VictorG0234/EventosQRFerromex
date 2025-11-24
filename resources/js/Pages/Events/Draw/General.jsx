import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/Components/ui/alert-dialog';
import { Trophy, Sparkles, ArrowLeft, Users, CheckCircle, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function DrawGeneral({ auth, event, winners, winners_count, eligible_count, can_raffle }) {
    const { flash } = usePage().props;
    const [isDrawing, setIsDrawing] = useState(false);
    const [winnersList, setWinnersList] = useState(winners || []);
    const [winnersCount, setWinnersCount] = useState(winners_count || 0);
    const [reselectingWinner, setReselectingWinner] = useState(null); // ID del ganador que se está re-seleccionando
    const [showDrawModal, setShowDrawModal] = useState(false);
    const [showReselectModal, setShowReselectModal] = useState(false);
    const [selectedWinnerId, setSelectedWinnerId] = useState(null);

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleDraw = async () => {
        setIsDrawing(true);
        
        try {
            const response = await fetch(route('events.draw.general.execute', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    winners_count: 15,
                    send_notification: false,
                    reset_previous: false
                })
            });

            const result = await response.json();

            if (result.success) {
                toast.success(result.message);
                // Actualizar la lista de ganadores
                setWinnersList(result.winners);
                setWinnersCount(result.winners_count);
                // Recargar la página para mostrar los ganadores actualizados
                window.location.reload();
            } else {
                toast.error(result.message || 'Error al realizar la rifa general');
            }
        } catch (error) {
            toast.error('Error al realizar la rifa general: ' + error.message);
        } finally {
            setIsDrawing(false);
        }
    };

    const handleReselectWinner = async (winnerId) => {
        setSelectedWinnerId(winnerId);
        setShowReselectModal(true);
    };

    const confirmReselectWinner = async () => {
        if (!selectedWinnerId) return;

        setReselectingWinner(selectedWinnerId);
        setShowReselectModal(false);
        
        try {
        
            const response = await fetch(route('events.draw.general.reselect', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    guest_id: selectedWinnerId
                })
            });

            const result = await response.json();

            if (result.success) {
                toast.success(result.message);
                // Recargar la página para mostrar el nuevo ganador
                window.location.reload();
            } else {
                toast.error(result.message || 'Error al re-seleccionar ganador');
            }
        } catch (error) {
            toast.error('Error al re-seleccionar ganador: ' + error.message);
        } finally {
            setReselectingWinner(null);
            setSelectedWinnerId(null);
        }
    };

    const confirmDraw = () => {
        setShowDrawModal(false);
        handleDraw();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Link
                        href={route('events.raffle.index', event.id)}
                        className="mr-4 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    >
                        <ArrowLeft className="w-5 h-5" />
                    </Link>
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Rifa General - {event.name}
                        </h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300 mt-1">
                            Realiza la rifa general para seleccionar 15 ganadores
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Rifa General - ${event.name}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Estadísticas */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <Users className="w-8 h-8 text-blue-500 mr-3" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Elegibles</p>
                                        <p className="text-2xl font-bold text-gray-900">{eligible_count}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <Trophy className="w-8 h-8 text-yellow-500 mr-3" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Ganadores</p>
                                        <p className="text-2xl font-bold text-gray-900">{winnersCount}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <CheckCircle className="w-8 h-8 text-green-500 mr-3" />
                                    <div>
                                        <p className="text-sm font-medium text-gray-500">Estado</p>
                                        <p className="text-lg font-bold text-gray-900">
                                            Pendiente
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Lista de ganadores */}
                    {winnersList.length > 0 ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                    <Trophy className="w-5 h-5 mr-2 text-yellow-500" />
                                    Ganadores de la Rifa General ({winnersList.length})
                                </h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    {winnersList.map((winner, index) => (
                                        <div
                                            key={winner.id}
                                            className="bg-gradient-to-br from-yellow-50 to-yellow-100 border-2 border-yellow-300 rounded-lg p-4 relative"
                                        >
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center mb-2">
                                                        <span className="bg-yellow-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm mr-2">
                                                            {index + 1}
                                                        </span>
                                                        <h4 className="font-bold text-gray-900 text-lg">
                                                            {winner.name}
                                                        </h4>
                                                    </div>
                                                    <p className="text-sm text-gray-700 mb-1">
                                                        <span className="font-medium">Empresa:</span> {winner.company}
                                                    </p>
                                                    <p className="text-sm text-gray-700 mb-1">
                                                        <span className="font-medium">Número de empleado:</span> {winner.employee_number}
                                                    </p>
                                                    {winner.drawn_at && (
                                                        <p className="text-xs text-gray-500 mt-2">
                                                            Seleccionado: {winner.drawn_at}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="mt-3 pt-3 border-t border-yellow-200">
                                                <Button
                                                    onClick={() => handleReselectWinner(winner.id)}
                                                    disabled={reselectingWinner === winner.id || isDrawing}
                                                    variant="outline"
                                                    size="sm"
                                                    className="w-full bg-white hover:bg-yellow-50 border-yellow-300 text-yellow-700 hover:text-yellow-800 disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    {reselectingWinner === winner.id ? (
                                                        <>
                                                            <RefreshCw className="w-3 h-3 mr-2 animate-spin" />
                                                            Re-seleccionando...
                                                        </>
                                                    ) : (
                                                        <>
                                                            <RefreshCw className="w-3 h-3 mr-2" />
                                                            Volver a seleccionar
                                                        </>
                                                    )}
                                                </Button>
                                                
                                                <AlertDialog open={showReselectModal && selectedWinnerId === winner.id} onOpenChange={(open) => {
                                                    if (!open) {
                                                        setShowReselectModal(false);
                                                        setSelectedWinnerId(null);
                                                    }
                                                }}>
                                                    <AlertDialogContent className="max-w-md">
                                                        <AlertDialogHeader>
                                                            <div className="flex items-center justify-center mb-4">
                                                                <div className="bg-yellow-100 rounded-full p-3">
                                                                    <RefreshCw className="w-8 h-8 text-yellow-600" />
                                                                </div>
                                                            </div>
                                                            <AlertDialogTitle className="text-center text-2xl text-gray-900 dark:text-gray-100">
                                                                ¿Re-seleccionar este ganador?
                                                            </AlertDialogTitle>
                                                            <AlertDialogDescription className="text-center pt-4 space-y-3 text-gray-700 dark:text-gray-300">
                                                                <p className="text-base text-gray-700 dark:text-gray-300">
                                                                    Se seleccionará un <span className="font-bold text-yellow-600 dark:text-yellow-400">nuevo ganador aleatorio</span> para reemplazar a este ganador.
                                                                </p>
                                                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                                                                    <p className="text-sm text-blue-800 dark:text-blue-200">
                                                                        ✅ Los otros <span className="font-bold">14 ganadores</span> se mantendrán intactos.
                                                                    </p>
                                                                </div>
                                                                <div className="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 mt-2">
                                                                    <p className="text-xs text-gray-700 dark:text-gray-300">
                                                                        <span className="font-medium">Ganador actual:</span> {winner.name}
                                                                    </p>
                                                                    <p className="text-xs text-gray-700 dark:text-gray-300">
                                                                        <span className="font-medium">Empresa:</span> {winner.company}
                                                                    </p>
                                                                </div>
                                                            </AlertDialogDescription>
                                                        </AlertDialogHeader>
                                                        <AlertDialogFooter className="flex-col sm:flex-row gap-2 sm:gap-0">
                                                            <Button
                                                                variant="outline"
                                                                onClick={() => {
                                                                    setShowReselectModal(false);
                                                                    setSelectedWinnerId(null);
                                                                }}
                                                                disabled={reselectingWinner === winner.id}
                                                                className="w-full sm:w-auto"
                                                            >
                                                                Cancelar
                                                            </Button>
                                                            <Button
                                                                onClick={confirmReselectWinner}
                                                                disabled={reselectingWinner === winner.id}
                                                                className="w-full sm:w-auto bg-yellow-600 hover:bg-yellow-700"
                                                            >
                                                                {reselectingWinner === winner.id ? (
                                                                    <>
                                                                        <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                                                        Re-seleccionando...
                                                                    </>
                                                                ) : (
                                                                    <>
                                                                        <RefreshCw className="w-4 h-4 mr-2" />
                                                                        Confirmar
                                                                    </>
                                                                )}
                                                            </Button>
                                                        </AlertDialogFooter>
                                                    </AlertDialogContent>
                                                </AlertDialog>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-8 text-center">
                                <Trophy className="w-16 h-16 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">
                                    No hay ganadores aún
                                </h3>
                                <p className="text-gray-500 mb-6">
                                    Realiza la rifa general para seleccionar 15 ganadores.
                                </p>
                                <Button
                                    onClick={handleDraw}
                                    disabled={isDrawing}
                                    className="inline-flex items-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md transition-colors"
                                >
                                    <Sparkles className="w-4 h-4 mr-2" />
                                    Realizar Rifa General
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

