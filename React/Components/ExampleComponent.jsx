import React from 'react';

/**
 * Composant React d'exemple
 * 
 * @param {Object} props - Les propriétés du composant
 * @param {string} props.title - Titre à afficher
 * @param {string} props.message - Message à afficher
 * @param {Function} props.onClick - Callback appelé lors du clic
 */
const ExampleComponent = ({ title = 'Exemple', message = 'Bienvenue dans React !', onClick }) => {
    return (
        <div className="react-component-example">
            <h2>{title}</h2>
            <p>{message}</p>
            {onClick && (
                <button onClick={onClick} className="btn btn-primary">
                    Cliquez-moi
                </button>
            )}
        </div>
    );
};

export default ExampleComponent;

