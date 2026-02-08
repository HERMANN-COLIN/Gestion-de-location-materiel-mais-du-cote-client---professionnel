// src/utils/imageHelper.js

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

/**
 * Génère l'URL complète pour une image
 */
export function getImageUrl(photoPath) {
  if (!photoPath) {
    return '/placeholder.jpg';
  }

  // Si c'est déjà une URL complète
  if (photoPath.startsWith('http://') || photoPath.startsWith('https://')) {
    return photoPath;
  }

  // Si c'est un chemin absolu local
  if (photoPath.startsWith('/')) {
    return photoPath;
  }

  // Nettoyer le chemin
  let cleanPath = photoPath;
  
  // Retirer 'storage/' si présent au début
  if (cleanPath.startsWith('storage/')) {
    cleanPath = cleanPath.replace('storage/', '');
  }

  // Construire l'URL complète via Laravel storage
  return `${API_BASE_URL}/storage/${cleanPath}`;
}

/**
 * Obtient l'URL de la photo principale d'un matériel
 */
export function getMaterielPhotoUrl(materiel) {
  if (!materiel) {
    return '/placeholder.jpg';
  }

  // Si le matériel a des photos
  if (materiel.photos && Array.isArray(materiel.photos) && materiel.photos.length > 0) {
    return getImageUrl(materiel.photos[0].url_photo);
  }

  // Si le matériel a une propriété photo directe
  if (materiel.photo) {
    return getImageUrl(materiel.photo);
  }

  // Si le matériel a une url_photo directe
  if (materiel.url_photo) {
    return getImageUrl(materiel.url_photo);
  }

  return '/placeholder.jpg';
}

/**
 * Gestionnaire d'erreur pour les images
 */
export function handleImageError(event) {
  event.target.src = '/placeholder.jpg';
  event.target.onerror = null; // Éviter les boucles infinies
}