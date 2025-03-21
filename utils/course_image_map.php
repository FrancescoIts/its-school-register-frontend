<?php
// Mappa dei corsi che condividono la stessa immagine
$course_image_map = [
    "I.C.T. System Developer" => "ict.jpg",
    "Industrial Software Developer" => "ict.jpg",
    "Marketing Technologist" => "moda.jpg",
    "Maestri Artigiani" => "moda.jpg",
    "Agroalimentare" => "agri.jpg",
    "Commercio alimentare: Food Manager" => "agri.jpg",
    "Agrifood Tech: Territory, Communication, Commercial and Web-Marketing" => "agri.jpg",
    "Logistica 5.0 e supply chain del futuro" => "logistica.jpg",
    "Tecnico superiore per cartotecnica packaging e grafica" => "meccatronica.jpg"
];

/**
 * Restituisce il nome del file immagine per un corso specifico.
 * @param string $course Nome del corso
 * @return string Nome del file immagine
 */
function getCourseImage($course) {
    global $course_image_map;
    return $course_image_map[$course] ?? strtolower(str_replace(' ', '_', $course)) . ".jpg";
}
?>
