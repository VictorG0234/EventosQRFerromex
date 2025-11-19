import { Head } from "@inertiajs/react";
import { ArrowLeftIcon } from "@heroicons/react/24/outline";

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
              <div className="prose prose-blue max-w-none dark:prose-invert">
                {/* Sección de ejemplo - Reemplazar con el contenido real del PDF */}
                <section className="mb-8">
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    AVISO DE PRIVACIDAD RH
                  </h2>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    Ferrocarril Mexicano, S.A. de C.V., con domicilio en Calle
                    Bosque de Ciruelos #99, Colonia Bosques de las Lomas, Código
                    Postal 11700 en CDMX, es responsable de recabar sus datos
                    personales, del uso que se le dé a los mismos y de su
                    protección. Su información personal incluyendo los
                    considerados como sensibles de conformidad con lo
                    establecido en la Ley Federal de Protección de Datos
                    Personales en Posesión de los Particulares y su Reglamento,
                    que Usted ya ha proporcionado a su empleador Ferrocarril
                    Mexicano, S.A. de C.V., será utilizada para:
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    Integrar bases con sus datos que puedan ser utilizadas para
                    fines operativos internos y llevar a cabo los trámites
                    necesarios e indispensables para la administración de
                    personal, así como para el cumplimiento de la legislación
                    mexicana que le es obligatoria a Ferrocarril Mexicano, S.A.
                    de C.V.
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4"></p>
                  Así mismo si por alguna razón o situación no prevista fuera
                  necesario contar con algún otro dato que no hubiera
                  proporcionado o que no se le hubiera requerido durante su
                  proceso de contratación, la empresa le informará para que
                  Usted pueda en un momento determinado emitir su decisión para
                  el uso y protección de esos nuevos datos personales incluyendo
                  los sensibles.z
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    En virtud de lo expuesto, le pedimos que en este acto y si
                    así es su decisión unilateral, voluntaria y por su propio
                    derecho, nos otorgue su consentimiento para el uso y
                    protección de sus datos personales incluyendo los
                    considerados como sensibles de conformidad con lo
                    establecido en la Ley Federal de Protección de Datos
                    Personales en Posesión de los Particulares y su Reglamento,
                    marcando con X su decisión:
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    SI otorgo mi consentimiento para el uso y protección de mis
                    datos personales, incluyendo los considerados como
                    sensibles, en los términos que señala el presente aviso de
                    privacidad.
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    NO otorgo mi consentimiento para el uso y protección de mis
                    datos personales, incluyendo los considerados como
                    sensibles, en los términos que señala el presente aviso de
                    privacidad.
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    Usted tiene derecho de acceder, rectificar y cancelar sus
                    datos personales, así como de oponerse al tratamiento de los
                    mismos o revocar el consentimiento que para tal fin nos haya
                    otorgado, a través del procedimiento que hemos implementado.
                    Para conocer dicho procedimiento, los requisitos y plazos,
                    se puede poner en contacto con nuestra encargada de datos
                    personales la Lic. Cynthia Corina Morales Cambronero, al
                    teléfono 55 52463700 extensión: 3845
                  </p>
                  <p className="text-gray-700 dark:text-gray-300 leading-relaxed mb-4">
                    Le pedimos nos manifieste su consentimiento para que sus
                    datos personales incluyendo los considerados como sensibles,
                    puedan ser transferidos o compartidos dentro y/o fuera del
                    país como parte de la administración de personal que realiza
                    su empleador, entre las diversas empresas del Grupo.
                  </p>
                </section>

                {/* Información de contacto */}
                <section className="mt-12 pt-8 border-t-2 border-gray-200 dark:border-gray-700">
                  <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                    Contacto
                  </h2>
                  <div className="bg-blue-50 dark:bg-gray-700 rounded-lg p-6">
                    <p className="text-gray-700 dark:text-gray-300 mb-3">
                      Para cualquier duda o aclaración sobre este aviso de
                      privacidad, puedes contactarnos en:
                    </p>
                    <div className="space-y-2 text-gray-700 dark:text-gray-300">
                      <p>
                        <strong>Email:</strong> privacidad@empresa.com
                      </p>
                      <p>
                        <strong>Teléfono:</strong> +52 (55) 1234-5678
                      </p>
                      <p>
                        <strong>Dirección:</strong> [Dirección completa]
                      </p>
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
