import { useState, useEffect } from 'react';

/**
 * Hook React d'exemple
 * 
 * @param {string} initialValue - Valeur initiale
 * @returns {[string, Function]} Tuple contenant la valeur et la fonction de mise à jour
 */
export const useExample = (initialValue = '') => {
    const [value, setValue] = useState(initialValue);

    useEffect(() => {
        // Exemple d'effet
        console.log('Valeur changée:', value);
    }, [value]);

    return [value, setValue];
};

