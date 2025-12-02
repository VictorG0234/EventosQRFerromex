import React, { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { toast } from 'react-hot-toast';
import axios from 'axios';

export default function RaffleModal({ 
    isOpen, 
    onClose, 
    selectedPrizeCard, 
    event,
    onWinnerSelected 
}) {
    const [attendees, setAttendees] = useState([]);
    const [currentNameIndex, setCurrentNameIndex] = useState(0);
    const [winner, setWinner] = useState(null);
    const [winnerEntryId, setWinnerEntryId] = useState(null);
    const [isAnimating, setIsAnimating] = useState(false);
    const [isRaffling, setIsRaffling] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const [showButtons, setShowButtons] = useState(false);
    const [isConfirming, setIsConfirming] = useState(false);
    const animationIntervalRef = useRef(null);
    const autoStartedRef = useRef(false);
    const audioRef = useRef(null);

    // Cargar asistentes cuando se abre el modal
    useEffect(() => {
        if (selectedPrizeCard && !isRaffling && !isAnimating && isOpen) {
            loadAttendees();
        }
    }, [selectedPrizeCard, isRaffling, isAnimating, isOpen]);

    // Iniciar automáticamente la rifa cuando termine de cargar
    useEffect(() => {
        if (!isLoading && attendees.length > 0 && !isRaffling && !isAnimating && !winner && isOpen && !autoStartedRef.current) {
            autoStartedRef.current = true;
            // Delay para que se complete la transición del logo y título antes de iniciar la animación
            setTimeout(() => {
                startRaffleAnimation();
            }, 600);
        }
    }, [isLoading, attendees.length, isRaffling, isAnimating, winner, isOpen]);

    // Limpiar intervalo cuando el componente se desmonte
    useEffect(() => {
        return () => {
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
        };
    }, []);

    // Resetear estados cuando se cierra el modal
    useEffect(() => {
        if (!isOpen) {
            setWinner(null);
            setWinnerEntryId(null);
            setIsAnimating(false);
            setCurrentNameIndex(0);
            setIsRaffling(false);
            setIsLoading(true);
            setShowButtons(false);
            setIsConfirming(false);
            autoStartedRef.current = false;
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
        }
    }, [isOpen]);

    const loadAttendees = async () => {
        try {
            setIsLoading(true);
            // Si hay un premio seleccionado, pasar el prize_id y raffle_type para obtener solo los elegibles
            const url = selectedPrizeCard?.prizeId 
                ? route('events.raffle.attendees', event.id) + `?prize_id=${selectedPrizeCard.prizeId}&raffle_type=public`
                : route('events.raffle.attendees', event.id);
            
            const response = await fetch(url);
            const data = await response.json();
            setAttendees(data.attendees || []);
            setIsLoading(false);
        } catch (error) {
            toast.error('Error al cargar la lista de asistentes');
            setIsLoading(false);
        }
    };

    const startRaffleAnimation = async () => {
        if (attendees.length === 0) {
            toast.error('No hay asistentes disponibles para la rifa');
            return;
        }

        // Reproducir audio al iniciar la rifa
        if (audioRef.current) {
            try {
                audioRef.current.currentTime = 0; // Reiniciar el audio
                const playPromise = audioRef.current.play();
                if (playPromise !== undefined) {
                    playPromise.catch(error => {
                        // Si el navegador bloquea la reproducción automática, solo mostrar en consola
                        console.log('No se pudo reproducir el audio automáticamente:', error);
                    });
                }
            } catch (error) {
                console.log('Error al reproducir audio:', error);
            }
        }

        setIsAnimating(true);
        setWinner(null);
        // Iniciar con un índice aleatorio en lugar de 0 para que se vea movimiento inmediato
        setCurrentNameIndex(Math.floor(Math.random() * attendees.length));
        setIsRaffling(true);

        // Iniciar la animación inmediatamente mientras se hace el fetch en paralelo
        // Usar una variable mutable para el índice del ganador que se actualizará cuando llegue el resultado
        let finalWinnerIndex = 0;
        let raffleResult = null;
        let winnerReceived = false;

        // Iniciar la animación de nombres aleatorios inmediatamente
        const totalDuration = 10000; // 10 segundos
        const constantSpeed = 40; // ms entre cambios (velocidad rápida constante)
        const slowDownStart = 0.6; // Empezar a desacelerar al 65% (más temprano para transición más suave)
        const finalSlowDownStart = 0.85; // Última fase de desaceleración al 90%
        const startTime = Date.now();
        let lastUpdateTime = startTime - constantSpeed;

        // Función para cambiar el nombre (extraída para reutilizar)
        const changeName = (currentProgress) => {
            // Si aún no tenemos el ganador, mostrar nombres aleatorios
            if (!winnerReceived) {
                const randomIndex = Math.floor(Math.random() * attendees.length);
                setCurrentNameIndex(randomIndex);
                return;
            }

            // Fase 1: Desaceleración gradual (65% - 90%)
            if (currentProgress > slowDownStart && currentProgress <= finalSlowDownStart) {
                const phaseProgress = (currentProgress - slowDownStart) / (finalSlowDownStart - slowDownStart);
                // Gradualmente reducir el rango de nombres cercanos
                const maxRange = Math.max(5, Math.floor(attendees.length * 0.15 * (1 - phaseProgress)));
                const offset = Math.floor(Math.random() * maxRange * 2) - maxRange;
                const targetIndex = (finalWinnerIndex + offset + attendees.length) % attendees.length;
                setCurrentNameIndex(targetIndex);
            }
            // Fase 2: Desaceleración final (90% - 100%) - siempre nombres muy cercanos al ganador
            else if (currentProgress > finalSlowDownStart) {
                const finalPhaseProgress = (currentProgress - finalSlowDownStart) / (1 - finalSlowDownStart);
                // En los últimos momentos, solo mostrar nombres muy cercanos o el ganador
                if (finalPhaseProgress > 0.7 || Math.random() < 0.6) {
                    // Mostrar el ganador directamente
                    setCurrentNameIndex(finalWinnerIndex);
                } else {
                    // Mostrar nombres inmediatamente adyacentes
                    const range = Math.max(1, Math.floor(2 * (1 - finalPhaseProgress)));
                    const offset = Math.floor(Math.random() * range * 2) - range;
                    const targetIndex = (finalWinnerIndex + offset + attendees.length) % attendees.length;
                    setCurrentNameIndex(targetIndex);
                }
            } else {
                // Antes del 65%, mostrar nombres aleatorios
                const randomIndex = Math.floor(Math.random() * attendees.length);
                setCurrentNameIndex(randomIndex);
            }
        };

        // Hacer el primer cambio inmediatamente usando setTimeout para forzar ejecución
        setTimeout(() => {
            changeName(0);
        }, 0);

        const animate = () => {
            const now = Date.now();
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / totalDuration, 1); // Asegurar que no exceda 1

            // Calcular velocidad: constante hasta 65%, luego desaceleración gradual y suave
            let speed;
            if (progress < slowDownStart) {
                // Primera parte (0-65%): velocidad constante rápida
                speed = constantSpeed;
            } else if (progress < finalSlowDownStart) {
                // Segunda parte (65-90%): desaceleración gradual
                const phaseProgress = (progress - slowDownStart) / (finalSlowDownStart - slowDownStart);
                // Curva suave de desaceleración (ease-out cubic)
                const easedProgress = 1 - Math.pow(1 - phaseProgress, 3);
                speed = constantSpeed + (easedProgress * 200); // Desaceleración gradual
            } else {
                // Última parte (90-100%): desaceleración final muy lenta
                const finalPhaseProgress = (progress - finalSlowDownStart) / (1 - finalSlowDownStart);
                // Curva muy suave para el final (ease-out quartic)
                const easedFinalProgress = 1 - Math.pow(1 - finalPhaseProgress, 4);
                speed = constantSpeed + 200 + (easedFinalProgress * 800); // Desaceleración final muy lenta
            }

            // Si ha pasado el tiempo necesario según la velocidad actual, cambiar nombre
            // O si es el primer frame (lastUpdateTime está en el pasado), cambiar inmediatamente
            if (now - lastUpdateTime >= speed || lastUpdateTime < startTime) {
                changeName(progress);
                lastUpdateTime = now;
            }

            // Después de completar la animación, mostrar el ganador definitivo
            if (progress >= 1) {
                cancelAnimationFrame(animationIntervalRef.current);
                // Asegurar que se muestra el ganador correcto
                setCurrentNameIndex(finalWinnerIndex);
                // Reducir el tiempo de espera antes de mostrar el ganador (de 300ms a 100ms)
                setTimeout(() => {
                    // Buscar el ganador completo en la lista de attendees para obtener todos los datos (incluyendo compania)
                    const fullWinnerData = attendees.find(a => a.id === raffleResult.winner.id) || raffleResult.winner;
                    setWinner(fullWinnerData);
                    setWinnerEntryId(raffleResult.entry_id); // Guardar el ID de la entrada
                    setIsAnimating(false);
                    setIsRaffling(false); // Resetear el estado de rifa para habilitar el botón
                    
                    // Lanzar confetti con efecto de nieve
                    if (window.confetti) {
                        const duration = 6 * 1000;
                        const animationEnd = Date.now() + duration;
                        let skew = 1;

                        function randomInRange(min, max) {
                            return Math.random() * (max - min) + min;
                        }

                        (function frame() {
                            const timeLeft = animationEnd - Date.now();
                            const ticks = Math.max(200, 500 * (timeLeft / duration));
                            skew = Math.max(0.8, skew - 0.001);
                            
                            window.confetti({
                                particleCount: 1,
                                startVelocity: 0,
                                ticks: ticks,
                                origin: {
                                    x: Math.random(),
                                    y: Math.random() * skew,
                                },
                                colors: ["#ed2d36"],
                                shapes: ["circle"],
                                gravity: randomInRange(0.4, 2),
                                scalar: randomInRange(0.4, 2),
                                drift: randomInRange(-0.4, 1),
                            });
                            
                            if (timeLeft > 0) {
                                requestAnimationFrame(frame);
                            }
                        })();
                    }
                    
                    // Mostrar botones después de 9 segundos
                    setTimeout(() => {
                        setShowButtons(true);
                    }, 9000);
                }, 100); // Esperar un momento antes de mostrar el ganador (reducido para transición más rápida)
            } else {
                animationIntervalRef.current = requestAnimationFrame(animate);
            }
        };

        // Iniciar la animación con requestAnimationFrame para mejor rendimiento
        animationIntervalRef.current = requestAnimationFrame(animate);

        // Hacer la petición en paralelo mientras la animación ya está corriendo
        try {
            const response = await axios.post(route('events.raffle.draw-single', [event.id, selectedPrizeCard.prizeId]), {
                card_index: selectedPrizeCard.index - 1,
                reset_previous: selectedPrizeCard.isWon
            });

            raffleResult = response.data;

            if (!raffleResult.success) {
                setIsAnimating(false);
                setIsRaffling(false);
                if (animationIntervalRef.current) {
                    cancelAnimationFrame(animationIntervalRef.current);
                }
                toast.error(raffleResult.message || raffleResult.error || 'Error al realizar la rifa');
                return;
            }
        } catch (error) {
            setIsAnimating(false);
            setIsRaffling(false);
            if (animationIntervalRef.current) {
                cancelAnimationFrame(animationIntervalRef.current);
            }
            
            const errorMessage = error.response?.data?.message || 
                               error.response?.data?.error || 
                               error.message || 
                               'Error al realizar la rifa';
            toast.error(errorMessage);
            return;
        }

            // Encontrar el índice del ganador en la lista de asistentes
            const winnerIndex = attendees.findIndex(a => a.id === raffleResult.winner.id);
            finalWinnerIndex = winnerIndex >= 0 ? winnerIndex : 0;
            winnerReceived = true; // Marcar que ya tenemos el ganador
    };

    const handleCloseModal = () => {
        if (animationIntervalRef.current) {
            cancelAnimationFrame(animationIntervalRef.current);
        }
        
        // Cerrar el modal primero
        onClose();
        
        // Si hay un ganador confirmado, notificar al componente padre para recargar
        if (winner && onWinnerSelected) {
            // Usar setTimeout para asegurar que el modal se cierre antes de recargar
            setTimeout(() => {
                onWinnerSelected(winner);
            }, 100);
        }
    };

    const handleConfirmWinner = async () => {
        if (!winnerEntryId) {
            toast.error('No hay ganador seleccionado');
            return;
        }

        setIsConfirming(true);
        try {
            const response = await axios.post(route('events.raffle.confirm-winner', [event.id, selectedPrizeCard.prizeId]), {
                entry_id: winnerEntryId,
                send_notification: true
            });

            const result = response.data;

            if (result.success) {
                toast.success('Premio entregado exitosamente');
                // Cerrar el modal y recargar
                handleCloseModal();
            } else {
                toast.error(result.message || 'Error al confirmar el ganador');
                setIsConfirming(false);
            }
        } catch (error) {
            const errorMessage = error.response?.data?.message || 
                               error.response?.data?.error || 
                               error.message || 
                               'Error al confirmar el ganador';
            toast.error(errorMessage);
            setIsConfirming(false);
        }
    };

    const handleReraffle = () => {
        // Resetear estados para volver a rifar
        setWinner(null);
        setWinnerEntryId(null);
        setShowButtons(false);
        setIsAnimating(false);
        setIsRaffling(false);
        autoStartedRef.current = false;
        
        // Reiniciar la rifa
        if (animationIntervalRef.current) {
            cancelAnimationFrame(animationIntervalRef.current);
        }
        
        // Esperar un momento antes de iniciar de nuevo
        setTimeout(() => {
            startRaffleAnimation();
        }, 300);
    };

    // La label es la compania del empleado
    const getCurrentDisplayEmployeeNumberLabel = () => {
        if (winner) {
            return winner.compania;
        }
        if (attendees.length > 0 && currentNameIndex < attendees.length) {
            return attendees[currentNameIndex]?.compania || '';
        }
        return '';
    };

    const getCurrentDisplayEmployeeNumber = () => {
        if (winner) {
            return winner.employee_number;
        }
        if (attendees.length > 0 && currentNameIndex < attendees.length) {
            return attendees[currentNameIndex]?.employee_number || '';
        }
        return '';
    };

    if (!selectedPrizeCard) return null;

    return (
        <Dialog open={isOpen} onOpenChange={(open) => !open && handleCloseModal()}>
            <DialogContent className="raffle-modal" hideClose>
                {/* Audio para la animación de la rifa */}
                <audio ref={audioRef} preload="auto">
                    <source src="/audio/rifa.mp3" type="audio/mpeg" />
                    Tu navegador no soporta el elemento de audio.
                </audio>
                
                {/* Área blanca central para el contenido */}
                <div className="raffle-modal-content"></div>
                
                <div className="raffle-modal-inner">
                    <button
                        onClick={handleCloseModal}
                        className="raffle-modal-close"
                    >
                        <span className="sr-only">Cerrar</span>
                        ✕
                    </button>

                    <div className="raffle-title">
                        <img 
                            src="/images/logo-gm.png" 
                            alt="GrupoMéxico" 
                            className={isLoading && !winner ? "raffle-logo-large" : "raffle-logo"} 
                        />
                    </div>
                    <p className={isLoading && !winner ? "raffle-subtitle-large" : "raffle-subtitle"}>
                        Sorteo de Fin de Año
                    </p>

                    {/* Estado durante animación */}
                    {isAnimating && !winner && (
                        <>
                            <div className="raffle-employee-number-box raffle-fade-in">
                                <p className="raffle-employee-number-animated">
                                    {getCurrentDisplayEmployeeNumberLabel()}
                                </p>
                                <p className="raffle-employee-number-animated">
                                    {getCurrentDisplayEmployeeNumber() || '---'}
                                </p>
                            </div>
                            
                            <div className="raffle-prize-box raffle-fade-in-delay">
                                <p className="raffle-prize-text">
                                    {selectedPrizeCard?.prizeName || 'PREMIO'}
                                </p>
                            </div>
                        </>
                    )}

                    {/* Estado ganador */}
                    {winner && (
                        <>                            
                            <div className="raffle-employee-number-box raffle-fade-in">
                                <p className="raffle-employee-number-label">
                                    {winner.compania}
                                </p>
                                <p className="raffle-employee-number-large">
                                    {winner.employee_number}
                                </p>
                            </div>
                            
                            <div className="raffle-prize-box raffle-fade-in-delay">
                                <p className="raffle-prize-text">
                                    {selectedPrizeCard?.prizeName?.toUpperCase() || 'PREMIO'}
                                </p>
                            </div>
                            
                            <div className="mb-6 raffle-fade-in-delay-2">
                                <p className="raffle-winner-text">
                                    ¡Ganador!
                                </p>
                                <p className="raffle-winner-name">
                                    {winner.name}
                                </p>
                            </div>

                            {/* Botones - siempre ocupan espacio pero aparecen después de 9 segundos */}
                            <div 
                                className={`mt-6 flex gap-3 justify-center transition-opacity duration-500 ${
                                    showButtons ? 'opacity-100' : 'opacity-0 pointer-events-none'
                                }`}
                                style={{ minHeight: '48px' }}
                            >
                                <Button
                                    onClick={handleConfirmWinner}
                                    disabled={isConfirming}
                                    variant="outline"
                                    className="text-sm px-4 py-2 raffle-button"
                                >
                                    {isConfirming ? (
                                        <>
                                            Confirmando...
                                        </>
                                    ) : (
                                        'Premio Entregado'
                                    )}
                                </Button>
                                <Button
                                    onClick={handleReraffle}
                                    disabled={isConfirming || isRaffling}
                                    variant="outline"
                                    className="text-sm px-4 py-2 raffle-button"
                                >
                                    Volver a rifar
                                </Button>
                            </div>
                        </>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}

