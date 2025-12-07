# ReactBundle v2.0 - Real-World Examples

Practical examples for common use cases with ReactBundle.

**Reading time:** ~30 minutes  
**Difficulty:** Beginner to Intermediate  
**Last updated:** 2024

---

## Table of Contents

1. [Basic Components](#basic-components)
2. [Forms & Input](#forms--input)
3. [Data Display](#data-display)
4. [Real-time Features](#real-time-features)
5. [Dashboard & Analytics](#dashboard--analytics)
6. [E-commerce](#e-commerce)

---

## Basic Components

### Example 1: Welcome Card

**Component:** `assets/React/Components/WelcomeCard.jsx`
```jsx
import React from 'react';

const WelcomeCard = ({ title, message, userName, actionUrl, actionText }) => {
    return (
        <div className="welcome-card">
            <div className="welcome-card__content">
                <h2 className="welcome-card__title">{title}</h2>
                <p className="welcome-card__message">{message}</p>
                
                {userName && (
                    <p className="welcome-card__greeting">
                        Welcome, <strong>{userName}</strong>!
                    </p>
                )}
                
                {actionUrl && (
                    <a href={actionUrl} className="welcome-card__button">
                        {actionText || 'Get Started'}
                    </a>
                )}
            </div>
        </div>
    );
};

export default WelcomeCard;
```

**CSS:** `assets/css/components/welcome-card.css`
```css
.welcome-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    padding: 40px;
    color: white;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.welcome-card__title {
    font-size: 28px;
    margin: 0 0 16px 0;
}

.welcome-card__message {
    font-size: 16px;
    opacity: 0.9;
    margin: 0 0 16px 0;
}

.welcome-card__greeting {
    font-size: 14px;
    margin: 16px 0;
}

.welcome-card__button {
    display: inline-block;
    background: white;
    color: #667eea;
    padding: 12px 32px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    margin-top: 16px;
    transition: transform 0.2s;
}

.welcome-card__button:hover {
    transform: translateY(-2px);
}
```

**Usage:** `templates/welcome.html.twig`
```twig
{% extends 'base.html.twig' %}

{% block content %}
    {{ react_component('WelcomeCard', {
        title: 'Welcome to Our Platform',
        message: 'Create amazing things with React and Symfony',
        userName: app.user.username,
        actionUrl: path('dashboard'),
        actionText: 'Go to Dashboard'
    }) }}
{% endblock %}
```

---

## Forms & Input

### Example 2: Contact Form

**Component:** `assets/React/Components/ContactForm.jsx`
```jsx
import React, { useState } from 'react';

const ContactForm = ({ csrf_token, recipientEmail }) => {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        subject: '',
        message: '',
    });
    
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(false);
    
    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value,
        }));
    };
    
    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setStatus(null);
        
        try {
            const response = await fetch('/api/contact', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf_token,
                },
                body: JSON.stringify({
                    ...formData,
                    recipient: recipientEmail,
                }),
            });
            
            if (!response.ok) {
                throw new Error('Failed to send message');
            }
            
            setStatus({ type: 'success', message: 'Message sent successfully!' });
            setFormData({ name: '', email: '', subject: '', message: '' });
        } catch (error) {
            setStatus({ type: 'error', message: error.message });
        } finally {
            setLoading(false);
        }
    };
    
    return (
        <form className="contact-form" onSubmit={handleSubmit}>
            <h3 className="contact-form__title">Contact Us</h3>
            
            {status && (
                <div className={`contact-form__status contact-form__status--${status.type}`}>
                    {status.message}
                </div>
            )}
            
            <div className="contact-form__group">
                <label htmlFor="name" className="contact-form__label">
                    Name *
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    className="contact-form__input"
                    value={formData.name}
                    onChange={handleChange}
                    required
                    disabled={loading}
                />
            </div>
            
            <div className="contact-form__group">
                <label htmlFor="email" className="contact-form__label">
                    Email *
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    className="contact-form__input"
                    value={formData.email}
                    onChange={handleChange}
                    required
                    disabled={loading}
                />
            </div>
            
            <div className="contact-form__group">
                <label htmlFor="subject" className="contact-form__label">
                    Subject *
                </label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    className="contact-form__input"
                    value={formData.subject}
                    onChange={handleChange}
                    required
                    disabled={loading}
                />
            </div>
            
            <div className="contact-form__group">
                <label htmlFor="message" className="contact-form__label">
                    Message *
                </label>
                <textarea
                    id="message"
                    name="message"
                    className="contact-form__textarea"
                    rows="5"
                    value={formData.message}
                    onChange={handleChange}
                    required
                    disabled={loading}
                />
            </div>
            
            <button
                type="submit"
                className="contact-form__button"
                disabled={loading}
            >
                {loading ? 'Sending...' : 'Send Message'}
            </button>
        </form>
    );
};

export default ContactForm;
```

**Controller:** `src/Controller/ContactController.php`
```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class ContactController extends AbstractController
{
    public function form(): Response
    {
        return $this->render('contact.html.twig', [
            'admin_email' => $this->getParameter('admin_email'),
        ]);
    }
    
    public function submit(
        Request $request,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        // Send email
        $email = (new Email())
            ->from($data['email'])
            ->to($data['recipient'])
            ->subject($data['subject'])
            ->html($data['message']);
        
        $mailer->send($email);
        
        return new JsonResponse(['success' => true]);
    }
}
```

**Usage:** `templates/contact.html.twig`
```twig
{% extends 'base.html.twig' %}

{% block content %}
    <div class="container">
        <h1>Contact Us</h1>
        {{ react_component('ContactForm', {
            csrf_token: csrf_token('api'),
            recipientEmail: admin_email
        }) }}
    </div>
{% endblock %}
```

---

## Data Display

### Example 3: User Table with Pagination

**Component:** `assets/React/Components/UserTable.jsx`
```jsx
import React, { useState, useEffect } from 'react';

const UserTable = ({ initialUsers, totalCount }) => {
    const [users, setUsers] = useState(initialUsers);
    const [page, setPage] = useState(1);
    const [loading, setLoading] = useState(false);
    const itemsPerPage = 10;
    const totalPages = Math.ceil(totalCount / itemsPerPage);
    
    const handlePageChange = async (newPage) => {
        setLoading(true);
        try {
            const response = await fetch(`/api/users?page=${newPage}`);
            const data = await response.json();
            setUsers(data.users);
            setPage(newPage);
        } finally {
            setLoading(false);
        }
    };
    
    return (
        <div className="user-table">
            <div className="user-table__header">
                <h3>Users ({totalCount})</h3>
            </div>
            
            <table className="user-table__table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {users.map(user => (
                        <tr key={user.id}>
                            <td>{user.name}</td>
                            <td>{user.email}</td>
                            <td>
                                <span className={`badge badge--${user.role}`}>
                                    {user.role}
                                </span>
                            </td>
                            <td>{new Date(user.createdAt).toLocaleDateString()}</td>
                            <td>
                                <a href={`/users/${user.id}`} className="btn-link">
                                    View
                                </a>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
            
            <div className="user-table__pagination">
                <button
                    disabled={page === 1 || loading}
                    onClick={() => handlePageChange(page - 1)}
                    className="btn"
                >
                    Previous
                </button>
                
                <span className="pagination__info">
                    Page {page} of {totalPages}
                </span>
                
                <button
                    disabled={page === totalPages || loading}
                    onClick={() => handlePageChange(page + 1)}
                    className="btn"
                >
                    Next
                </button>
            </div>
        </div>
    );
};

export default UserTable;
```

**Controller:** `src/Controller/UserController.php`
```php
<?php
class UserController extends AbstractController
{
    public function list(UserRepository $repo, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $perPage = 10;
        
        $users = $repo->findBy(
            [],
            ['createdAt' => 'DESC'],
            $perPage,
            ($page - 1) * $perPage
        );
        
        $total = $repo->count([]);
        
        return $this->render('users/list.html.twig', [
            'users' => array_map(fn($u) => [
                'id' => $u->getId(),
                'name' => $u->getName(),
                'email' => $u->getEmail(),
                'role' => $u->getRole(),
                'createdAt' => $u->getCreatedAt()->format('Y-m-d'),
            ], $users),
            'total' => $total,
        ]);
    }
}
```

---

## Real-time Features

### Example 4: Live Notification Badge

**Component:** `assets/React/Components/NotificationBell.jsx`
```jsx
import React, { useState, useEffect } from 'react';

const NotificationBell = ({ userId, apiToken }) => {
    const [count, setCount] = useState(0);
    const [isOpen, setIsOpen] = useState(false);
    const [notifications, setNotifications] = useState([]);
    
    useEffect(() => {
        // Poll for notifications every 10 seconds
        const interval = setInterval(() => {
            fetchNotifications();
        }, 10000);
        
        // Initial fetch
        fetchNotifications();
        
        return () => clearInterval(interval);
    }, []);
    
    const fetchNotifications = async () => {
        try {
            const response = await fetch(`/api/notifications?user=${userId}`, {
                headers: {
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            const data = await response.json();
            setCount(data.unreadCount);
            setNotifications(data.notifications);
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        }
    };
    
    const markAsRead = async (notificationId) => {
        try {
            await fetch(`/api/notifications/${notificationId}/read`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${apiToken}`,
                },
            });
            fetchNotifications();
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };
    
    return (
        <div className="notification-bell">
            <button
                className="notification-bell__button"
                onClick={() => setIsOpen(!isOpen)}
                aria-label="Notifications"
            >
                <svg className="icon" viewBox="0 0 24 24" width="24" height="24">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                {count > 0 && (
                    <span className="notification-bell__badge">{count}</span>
                )}
            </button>
            
            {isOpen && (
                <div className="notification-bell__dropdown">
                    {notifications.length === 0 ? (
                        <p className="notification-bell__empty">No notifications</p>
                    ) : (
                        <div className="notification-bell__list">
                            {notifications.map(notif => (
                                <div
                                    key={notif.id}
                                    className={`notification-item ${!notif.read ? 'notification-item--unread' : ''}`}
                                    onClick={() => markAsRead(notif.id)}
                                >
                                    <p className="notification-item__text">
                                        {notif.message}
                                    </p>
                                    <span className="notification-item__time">
                                        {new Date(notif.createdAt).toLocaleTimeString()}
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
};

export default NotificationBell;
```

---

## Dashboard & Analytics

### Example 5: Analytics Chart

**Component:** `assets/React/Components/AnalyticsChart.jsx`
```jsx
import React, { useEffect, useState } from 'react';
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

const AnalyticsChart = ({ metricName, period = '30d' }) => {
    const [data, setData] = useState([]);
    const [loading, setLoading] = useState(true);
    
    useEffect(() => {
        fetchData();
    }, [period]);
    
    const fetchData = async () => {
        setLoading(true);
        try {
            const response = await fetch(
                `/api/analytics/${metricName}?period=${period}`
            );
            const chartData = await response.json();
            setData(chartData);
        } finally {
            setLoading(false);
        }
    };
    
    if (loading) {
        return <div className="chart-loading">Loading chart...</div>;
    }
    
    return (
        <div className="analytics-chart">
            <h3>{metricName}</h3>
            <LineChart width={800} height={300} data={data}>
                <CartesianGrid strokeDasharray="3 3" />
                <XAxis dataKey="date" />
                <YAxis />
                <Tooltip />
                <Line type="monotone" dataKey="value" stroke="#8884d8" />
            </LineChart>
        </div>
    );
};

export default AnalyticsChart;
```

---

## E-commerce

### Example 6: Product Card with Add to Cart

**Component:** `assets/React/Components/ProductCard.jsx`
```jsx
import React, { useState } from 'react';

const ProductCard = ({ product, csrf_token, onAddToCart }) => {
    const [quantity, setQuantity] = useState(1);
    const [adding, setAdding] = useState(false);
    const [added, setAdded] = useState(false);
    
    const handleAddToCart = async () => {
        setAdding(true);
        
        try {
            const response = await fetch('/cart/add', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf_token,
                },
                body: JSON.stringify({
                    product_id: product.id,
                    quantity,
                }),
            });
            
            if (response.ok) {
                setAdded(true);
                onAddToCart?.();
                
                setTimeout(() => setAdded(false), 2000);
            }
        } finally {
            setAdding(false);
        }
    };
    
    const onSale = product.originalPrice > product.price;
    const discount = onSale
        ? Math.round((1 - product.price / product.originalPrice) * 100)
        : 0;
    
    return (
        <div className="product-card">
            <div className="product-card__image-wrapper">
                <img
                    src={product.image}
                    alt={product.name}
                    className="product-card__image"
                />
                {onSale && (
                    <span className="product-card__badge">
                        -{discount}%
                    </span>
                )}
            </div>
            
            <div className="product-card__content">
                <h4 className="product-card__title">{product.name}</h4>
                
                <p className="product-card__description">
                    {product.description}
                </p>
                
                <div className="product-card__rating">
                    <span className="stars">
                        {'⭐'.repeat(product.rating)}
                    </span>
                    <span className="reviews">({product.reviewCount})</span>
                </div>
                
                <div className="product-card__price">
                    <span className="price-current">${product.price}</span>
                    {onSale && (
                        <span className="price-original">
                            ${product.originalPrice}
                        </span>
                    )}
                </div>
                
                <div className="product-card__actions">
                    <div className="quantity-selector">
                        <button
                            onClick={() => setQuantity(Math.max(1, quantity - 1))}
                            disabled={quantity === 1}
                        >
                            -
                        </button>
                        <input
                            type="number"
                            min="1"
                            value={quantity}
                            onChange={(e) => setQuantity(parseInt(e.target.value) || 1)}
                        />
                        <button onClick={() => setQuantity(quantity + 1)}>
                            +
                        </button>
                    </div>
                    
                    <button
                        className="btn-add-cart"
                        onClick={handleAddToCart}
                        disabled={adding || !product.inStock}
                    >
                        {!product.inStock ? (
                            'Out of Stock'
                        ) : adding ? (
                            'Adding...'
                        ) : added ? (
                            '✓ Added'
                        ) : (
                            'Add to Cart'
                        )}
                    </button>
                </div>
            </div>
        </div>
    );
};

export default ProductCard;
```

---

## Summary

These examples demonstrate:

- ✅ Component structure and reusability
- ✅ Form handling and validation
- ✅ API communication
- ✅ State management
- ✅ Real-time updates
- ✅ Error handling
- ✅ User feedback

For more examples and advanced patterns, see:
- 📖 [Full Documentation](../README.md)
- ⚙️ [API Reference](API.md)
- 🐛 [Troubleshooting](TROUBLESHOOTING.md)

---

## License

MIT License - See [LICENSE](../LICENSE) for details
