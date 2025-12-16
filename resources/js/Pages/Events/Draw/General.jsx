import React, { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Button } from '@/Components/ui/button';
import { AlertDialog, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/Components/ui/alert-dialog';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Trophy, Sparkles, ArrowLeft, Users, CheckCircle, RefreshCw, Search, Package } from 'lucide-react';
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
    const [numberOfWinners, setNumberOfWinners] = useState('');
    const [validationError, setValidationError] = useState('');
    const [searchQuery, setSearchQuery] = useState('');
    const [deliveredPrizes, setDeliveredPrizes] = useState({});
    const [deliveringPrize, setDeliveringPrize] = useState(null);

    // Inicializar estado de premios entregados desde el servidor
    useEffect(() => {
        const initialDelivered = {};
        winnersList.forEach(winner => {
            if (winner.prize_delivered) {
                initialDelivered[winner.id] = true;
            }
        });
        setDeliveredPrizes(initialDelivered);
    }, [winnersList]);

    useEffect(() => {
        if (flash.success) {
            toast.success(flash.success);
        }
        if (flash.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const handleOpenDrawModal = () => {
        setNumberOfWinners('');
        setValidationError('');
        setShowDrawModal(true);
    };

    const validateNumberOfWinners = (value) => {
        const num = parseInt(value);
        if (!value || value.trim() === '') {
            setValidationError('El número de ganadores es requerido');
            return false;
        }
        if (isNaN(num) || num < 1) {
            setValidationError('El número de ganadores debe ser mayor a 0');
            return false;
        }
        if (num > eligible_count) {
            setValidationError(`El número de ganadores no puede ser mayor a ${eligible_count} (elegibles disponibles)`);
            return false;
        }
        setValidationError('');
        return true;
    };

    const handleDraw = async () => {
        if (!validateNumberOfWinners(numberOfWinners)) {
            return;
        }

        const numWinners = parseInt(numberOfWinners);
        setShowDrawModal(false);
        setIsDrawing(true);
        
        try {
            const response = await fetch(route('events.draw.general.execute', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    winners_count: numWinners,
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

    const handleDeliverPrize = async (winnerId) => {
        // Verificar si ya está entregado
        if (deliveredPrizes[winnerId]) {
            toast.error('Este premio ya fue entregado');
            return;
        }

        setDeliveringPrize(winnerId);
        
        try {
            const response = await fetch(route('events.draw.general.mark-delivered', event.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    guest_id: winnerId
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setDeliveredPrizes(prev => ({
                    ...prev,
                    [winnerId]: true
                }));
                toast.success('Premio marcado como entregado');
            } else {
                toast.error(data.message || 'Error al marcar el premio como entregado');
            }
        } catch (error) {
            console.error('Error:', error);
            toast.error('Error al marcar el premio como entregado');
        } finally {
            setDeliveringPrize(null);
        }
    };

    const filteredWinners = winnersList.filter(winner => {
        if (!searchQuery) return true;
        const query = searchQuery.toLowerCase();
        return (
            winner.name?.toLowerCase().includes(query) ||
            winner.employee_number?.toLowerCase().includes(query) ||
            winner.company?.toLowerCase().includes(query)
        );
    });

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

            // Verificar si la respuesta es OK antes de parsear JSON
            if (!response.ok) {
                const errorText = await response.text();
                let errorData;
                try {
                    errorData = JSON.parse(errorText);
                } catch (e) {
                    errorData = { message: errorText || `Error ${response.status}: ${response.statusText}` };
                }
                
                console.error('Error HTTP al re-seleccionar ganador:', {
                    status: response.status,
                    statusText: response.statusText,
                    error: errorData,
                    guest_id: selectedWinnerId
                });
                
                const errorMessage = errorData.message || errorData.error || `Error ${response.status}`;
                const errors = errorData.errors ? Object.values(errorData.errors).flat().join(', ') : '';
                toast.error(errors ? `${errorMessage}: ${errors}` : errorMessage);
                return;
            }

            const result = await response.json();
            console.log('Respuesta del servidor:', result);

            if (result.success) {
                toast.success(result.message);
                // Actualizar la lista de ganadores sin recargar
                if (result.winner) {
                    setWinnersList(prevWinners => {
                        // Encontrar el índice del ganador que se está reemplazando
                        const winnerIndex = prevWinners.findIndex(w => w.id === selectedWinnerId);
                        
                        if (winnerIndex === -1) {
                            console.error('No se encontró el ganador a reemplazar:', selectedWinnerId);
                            console.log('Lista actual:', prevWinners);
                            // Si no se encuentra, recargar la página
                            window.location.reload();
                            return prevWinners;
                        }
                        
                        // Crear nueva lista reemplazando el ganador en ese índice
                        const updatedWinners = [...prevWinners];
                        updatedWinners[winnerIndex] = {
                            id: result.winner.id,
                            name: result.winner.name,
                            company: result.winner.company,
                            employee_number: result.winner.employee_number,
                            drawn_at: result.winner.drawn_at || new Date().toLocaleString('es-MX')
                        };
                        
                        console.log('Ganador actualizado:', {
                            indice: winnerIndex,
                            anterior: prevWinners[winnerIndex],
                            nuevo: updatedWinners[winnerIndex],
                            listaCompleta: updatedWinners
                        });
                        
                        return updatedWinners;
                    });
                    // El conteo se mantiene igual ya que solo se reemplaza uno
                } else {
                    // Si no viene el winner, recargar la página
                    console.warn('No se recibió el winner en la respuesta, recargando página...');
                    window.location.reload();
                }
            } else {
                // Mostrar mensaje de error más detallado
                const errorMessage = result.message || result.error || 'Error al re-seleccionar ganador';
                const errors = result.errors ? Object.values(result.errors).flat().join(', ') : '';
                console.error('Error al re-seleccionar ganador:', {
                    message: errorMessage,
                    errors: result.errors,
                    fullResponse: result,
                    guest_id: selectedWinnerId
                });
                toast.error(errors ? `${errorMessage}: ${errors}` : errorMessage);
            }
        } catch (error) {
            console.error('Error de red al re-seleccionar ganador:', {
                error: error,
                message: error.message,
                stack: error.stack,
                guest_id: selectedWinnerId
            });
            toast.error('Error de conexión al re-seleccionar ganador: ' + error.message);
        } finally {
            setReselectingWinner(null);
            setSelectedWinnerId(null);
        }
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
                            Realiza la rifa general para seleccionar ganadores
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
                                            {winnersList.length > 0 ? 'Completada' : 'En progreso'}
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
                                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                                    <h3 className="text-lg font-semibold text-gray-900 flex items-center">
                                        <Trophy className="w-5 h-5 mr-2 text-yellow-500" />
                                        Ganadores de la Rifa General ({filteredWinners.length}/{winnersList.length})
                                    </h3>
                                    
                                    {/* Buscador */}
                                    <div className="relative w-full sm:w-64">
                                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                                        <input
                                            type="text"
                                            placeholder="Buscar ganador..."
                                            value={searchQuery}
                                            onChange={(e) => setSearchQuery(e.target.value)}
                                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                        />
                                    </div>
                                </div>

                                {filteredWinners.length === 0 ? (
                                    <div className="text-center py-8 text-gray-500">
                                        No se encontraron ganadores con "{searchQuery}"
                                    </div>
                                ) : (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-2">
                                        {filteredWinners.map((winner, index) => {
                                            const isDelivered = deliveredPrizes[winner.id];
                                            const originalIndex = winnersList.findIndex(w => w.id === winner.id);
                                            
                                            return (
                                                <div
                                                    key={winner.id}
                                                    className="rounded-lg p-2.5 relative"
                                                    style={{
                                                        background: isDelivered ? 'linear-gradient(to bottom right, #D1FAE5, #A7F3D0)' : 'linear-gradient(to bottom right, #FEE2E2, #FECACA)',
                                                        border: `2px solid ${isDelivered ? '#10B981' : '#D22730'}`
                                                    }}
                                                >
                                                    <div className="flex items-center gap-2 mb-2">
                                                        <span className="text-white rounded-full w-6 h-6 flex items-center justify-center font-bold text-xs flex-shrink-0"
                                                            style={{ backgroundColor: isDelivered ? '#10B981' : '#D22730' }}
                                                        >
                                                            {originalIndex + 1}
                                                        </span>
                                                        <h4 className="font-bold text-gray-900 text-sm leading-tight">
                                                            {winner.name}
                                                        </h4>
                                                    </div>
                                                    
                                                    <div className="flex items-center justify-between">
                                                        <p className="text-xs text-gray-700">
                                                            <span className="font-medium">No. empleado:</span> {winner.employee_number}
                                                        </p>
                                                        
                                                        <div className="flex items-center gap-1">
                                                            {!isDelivered ? (
                                                                <>
                                                                    <Button
                                                                        onClick={() => handleDeliverPrize(winner.id)}
                                                                        disabled={deliveringPrize === winner.id}
                                                                        variant="outline"
                                                                        size="sm"
                                                                        className="bg-white p-1 h-6 w-6 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                        style={{
                                                                            borderColor: '#10B981',
                                                                            color: '#10B981'
                                                                        }}
                                                                        title="Entregar premio"
                                                                    >
                                                                        {deliveringPrize === winner.id ? (
                                                                            <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                                                                        ) : (
                                                                            <Package className="w-3.5 h-3.5" />
                                                                        )}
                                                                    </Button>
                                                                    
                                                                    <Button
                                                                        onClick={() => handleReselectWinner(winner.id)}
                                                                        disabled={reselectingWinner === winner.id || isDrawing}
                                                                        variant="outline"
                                                                        size="sm"
                                                                        className="bg-white p-1 h-6 w-6 disabled:opacity-50 disabled:cursor-not-allowed"
                                                                        style={{
                                                                            borderColor: '#D22730',
                                                                            color: '#D22730'
                                                                        }}
                                                                        title="Volver a seleccionar"
                                                                    >
                                                                        {reselectingWinner === winner.id ? (
                                                                            <RefreshCw className="w-3.5 h-3.5 animate-spin" />
                                                                        ) : (
                                                                            <RefreshCw className="w-3.5 h-3.5" />
                                                                        )}
                                                                    </Button>
                                                                </>
                                                            ) : (
                                                                <div className="flex items-center text-green-700">
                                                                    <CheckCircle className="w-4 h-4" />
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>

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
                                                                    <div className="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-3 mt-2">
                                                                        <p className="text-xs text-gray-700 dark:text-gray-300">
                                                                            <span className="font-medium">Ganador actual:</span> {winner.name}
                                                                        </p>
                                                                        <p className="text-xs text-gray-700 dark:text-gray-300">
                                                                            <span className="font-medium">No. empleado:</span> {winner.employee_number}
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
                                            );
                                        })}
                                    </div>
                                )}
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
                                    Realiza la rifa general para seleccionar ganadores.
                                </p>
                                <Button
                                    onClick={handleOpenDrawModal}
                                    disabled={isDrawing || !can_raffle}
                                    className="inline-flex items-center px-4 py-2 text-white text-sm font-medium rounded-md transition-colors"
                                    style={{ backgroundColor: '#D22730' }}
                                >
                                    <Sparkles className="w-4 h-4 mr-2" />
                                    Realizar Rifa General
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Modal para ingresar número de ganadores */}
            <Dialog open={showDrawModal} onOpenChange={setShowDrawModal}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="text-2xl text-center text-gray-900 dark:text-gray-100">
                            Rifa General
                        </DialogTitle>
                        <DialogDescription className="text-center pt-2 text-gray-700 dark:text-gray-300">
                            Ingresa el número de ganadores que deseas seleccionar
                        </DialogDescription>
                    </DialogHeader>
                    <div className="py-4 space-y-4">
                        <div>
                            <label htmlFor="winners_count" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Número de ganadores
                            </label>
                            <input
                                id="winners_count"
                                type="number"
                                min="1"
                                max={eligible_count}
                                value={numberOfWinners}
                                onChange={(e) => {
                                    setNumberOfWinners(e.target.value);
                                    if (validationError) {
                                        validateNumberOfWinners(e.target.value);
                                    }
                                }}
                                onBlur={(e) => validateNumberOfWinners(e.target.value)}
                                placeholder={`Máximo: ${eligible_count}`}
                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm px-3 py-2 border"
                            />
                            {validationError && (
                                <p className="mt-1 text-sm text-red-600">{validationError}</p>
                            )}
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Debe ser mayor a 0 y menor o igual a {eligible_count} (elegibles disponibles)
                            </p>
                        </div>
                        <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3">
                            <p className="text-sm text-blue-800 dark:text-blue-200">
                                <span className="font-medium">Elegibles disponibles:</span> {eligible_count}
                            </p>
                        </div>
                    </div>
                    <DialogFooter className="flex-col sm:flex-row gap-2 sm:gap-0">
                        <Button
                            variant="outline"
                            onClick={() => {
                                setShowDrawModal(false);
                                setNumberOfWinners('');
                                setValidationError('');
                            }}
                            disabled={isDrawing}
                            className="w-full sm:w-auto"
                        >
                            Cancelar
                        </Button>
                        <Button
                            onClick={handleDraw}
                            disabled={isDrawing || !numberOfWinners || !!validationError}
                            className="w-full sm:w-auto bg-purple-600 hover:bg-purple-700"
                        >
                            {isDrawing ? (
                                <>
                                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                                    Realizando...
                                </>
                            ) : (
                                <>
                                    <Sparkles className="w-4 h-4 mr-2" />
                                    Realizar Rifa
                                </>
                            )}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}

