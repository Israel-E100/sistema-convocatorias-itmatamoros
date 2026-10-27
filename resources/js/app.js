const menuToggle = document.querySelector('[data-navbar-toggle]');
const mobileMenu = document.querySelector('[data-navbar-menu]');

if (menuToggle && mobileMenu) {
    const menuIcon = menuToggle.querySelector('[data-menu-icon]');
    const closeIcon = menuToggle.querySelector('[data-close-icon]');

    const setMenuOpen = (isOpen) => {
        menuToggle.setAttribute('aria-expanded', String(isOpen));
        menuToggle.setAttribute(
            'aria-label',
            isOpen ? 'Cerrar menú de navegación' : 'Abrir menú de navegación',
        );
        mobileMenu.classList.toggle('hidden', !isOpen);
        menuIcon?.classList.toggle('hidden', isOpen);
        closeIcon?.classList.toggle('hidden', !isOpen);
    };

    menuToggle.addEventListener('click', () => {
        setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
    });

    mobileMenu.addEventListener('click', (event) => {
        if (event.target instanceof HTMLAnchorElement) {
            setMenuOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
            setMenuOpen(false);
            menuToggle.focus();
        }
    });
}

const profileMenu = document.querySelector('[data-profile-menu]');

if (profileMenu) {
    const profileToggle = profileMenu.querySelector('[data-profile-toggle]');
    const profileDropdown = profileMenu.querySelector('[data-profile-dropdown]');

    if (profileToggle && profileDropdown) {
        const setProfileMenuOpen = (isOpen) => {
            profileToggle.setAttribute('aria-expanded', String(isOpen));
            profileToggle.setAttribute(
                'aria-label',
                isOpen ? 'Cerrar menú del perfil' : 'Abrir menú del perfil',
            );
            profileDropdown.classList.toggle('hidden', !isOpen);
        };

        profileToggle.addEventListener('click', () => {
            setProfileMenuOpen(profileToggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('click', (event) => {
            if (event.target instanceof Node && !profileMenu.contains(event.target)) {
                setProfileMenuOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && profileToggle.getAttribute('aria-expanded') === 'true') {
                setProfileMenuOpen(false);
                profileToggle.focus();
            }
        });
    }
}

const notificationsMenu = document.querySelector('[data-notifications-menu]');

if (notificationsMenu) {
    const notificationsToggle = notificationsMenu.querySelector('[data-notifications-toggle]');
    const notificationsDropdown = notificationsMenu.querySelector('[data-notifications-dropdown]');

    if (notificationsToggle && notificationsDropdown) {
        const setNotificationsOpen = (isOpen) => {
            notificationsToggle.setAttribute('aria-expanded', String(isOpen));
            notificationsDropdown.classList.toggle('hidden', !isOpen);
        };

        notificationsToggle.addEventListener('click', () => {
            setNotificationsOpen(notificationsToggle.getAttribute('aria-expanded') !== 'true');
        });

        document.addEventListener('click', (event) => {
            if (event.target instanceof Node && !notificationsMenu.contains(event.target)) {
                setNotificationsOpen(false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && notificationsToggle.getAttribute('aria-expanded') === 'true') {
                setNotificationsOpen(false);
                notificationsToggle.focus();
            }
        });
    }
}

const profileTypeSelect = document.querySelector('[data-profile-type]');
const plazaCodeField = document.querySelector('[data-plaza-code-field]');
const plazaCodeSelect = plazaCodeField?.querySelector('select');

if (profileTypeSelect && plazaCodeField && plazaCodeSelect) {
    const updatePlazaCodeRequirement = () => {
        const isTeacher = profileTypeSelect.value === 'aspirante_docente';
        plazaCodeField.classList.toggle('hidden', !isTeacher);
        plazaCodeSelect.required = isTeacher;
        plazaCodeSelect.disabled = !isTeacher;
    };

    profileTypeSelect.addEventListener('change', updatePlazaCodeRequirement);
    updatePlazaCodeRequirement();
}

const adminRoleSelect = document.querySelector('[data-admin-role]');
const adminProfileTypeSelect = document.querySelector('[data-admin-profile-type]');
const adminPlazaField = document.querySelector('[data-admin-plaza-field]');
const adminPlazaSelect = adminPlazaField?.querySelector('[data-admin-plaza-select]');

if (adminRoleSelect && adminProfileTypeSelect && adminPlazaField && adminPlazaSelect) {
    const updateAdminPlazaRequirement = () => {
        const requiresPlaza = adminRoleSelect.value === 'usuario'
            && adminProfileTypeSelect.value === 'aspirante_docente';
        adminPlazaField.classList.toggle('hidden', !requiresPlaza);
        adminPlazaSelect.required = requiresPlaza;
        adminPlazaSelect.disabled = !requiresPlaza;
    };

    adminRoleSelect.addEventListener('change', updateAdminPlazaRequirement);
    adminProfileTypeSelect.addEventListener('change', updateAdminPlazaRequirement);
    updateAdminPlazaRequirement();
}

const plazasBoard = document.querySelector('[data-plazas-board]');

if (plazasBoard) {
    const stateClasses = {
        ocupada: ['border-gray-300', 'bg-gray-100', 'text-gray-800'],
        disponible: ['border-emerald-300', 'bg-emerald-50', 'text-emerald-950'],
        ascenso_pendiente: ['border-blue-300', 'bg-blue-50', 'text-blue-950'],
        reservada: ['border-amber-300', 'bg-amber-50', 'text-amber-950'],
    };
    const allStateClasses = Object.values(stateClasses).flat();
    const updatedLabel = plazasBoard.querySelector('[data-plazas-updated]');
    const notice = plazasBoard.querySelector('[data-plaza-notice]');
    const canInteract = plazasBoard.dataset.canInteract === 'true';
    const closeNotice = () => {
        notice.classList.add('hidden');
        notice.classList.remove('flex');
        notice.setAttribute('aria-hidden', 'true');
    };

    const openNotice = () => {
        notice.classList.remove('hidden');
        notice.classList.add('flex');
        notice.setAttribute('aria-hidden', 'false');
        notice.querySelector('[data-plaza-notice-close]')?.focus();
    };

    notice?.querySelectorAll('[data-plaza-notice-close]').forEach((button) => {
        button.addEventListener('click', closeNotice);
    });

    notice?.addEventListener('click', (event) => {
        if (event.target === notice) {
            closeNotice();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && notice && !notice.classList.contains('hidden')) {
            closeNotice();
        }
    });

    plazasBoard.addEventListener('click', (event) => {
        const action = event.target.closest('[data-plaza-action]');

        if (!action || action.disabled) {
            return;
        }

        const card = action.closest('[data-plaza-state]');

        if (card.dataset.estado === 'disponible') {
            if (card.dataset.convocatoriaUrl) {
                window.location.assign(card.dataset.convocatoriaUrl);
            } else {
                openNotice();
            }
        } else if (card.dataset.estado === 'ascenso_pendiente') {
            card.querySelector('[data-plaza-claim-form]')?.requestSubmit();
        }
    });

    const refreshPlazas = async () => {
        try {
            const response = await fetch(plazasBoard.dataset.liveStateUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`La actualización del estado de plazas respondió ${response.status}.`);
            }

            const { plazas, actualizado: updatedAt } = await response.json();

            plazas.forEach((plaza) => {
                const card = plazasBoard.querySelector(`[data-plaza-state][data-codigo="${plaza.codigo}"]`);

                if (!card || !stateClasses[plaza.estado]) {
                    return;
                }

                const isMyPlaza = plaza.es_mi_plaza === true;
                card.classList.remove(...allStateClasses);
                card.classList.add(...stateClasses[plaza.estado]);
                card.dataset.estado = plaza.estado;
                card.dataset.convocatoriaUrl = plaza.convocatoria_url ?? '';
                card.dataset.isMyPlaza = String(isMyPlaza);

                const action = card.querySelector('[data-plaza-action]');
                const label = card.querySelector('[data-plaza-label]');
                const occupant = card.querySelector('[data-plaza-occupant]');
                const offerForm = card.querySelector('[data-plaza-offer-form]');
                const offerButton = card.querySelector('[data-plaza-offer-button]');
                const claimForm = card.querySelector('[data-plaza-claim-form]');
                const myBadge = card.querySelector('[data-plaza-my-badge]');

                if (action) {
                    action.disabled = !canInteract
                        || plaza.estado === 'ocupada'
                        || plaza.estado === 'reservada'
                        || (plaza.estado === 'ascenso_pendiente' && isMyPlaza);
                }
                if (label) {
                    label.textContent = plaza.etiqueta;
                }
                if (occupant) {
                    occupant.textContent = plaza.ocupante ? `Titular: ${plaza.ocupante}` : '';
                }
                if (offerForm) {
                    offerForm.hidden = !isMyPlaza;
                }
                if (myBadge) {
                    myBadge.hidden = !isMyPlaza;
                }
                if (offerButton) {
                    offerButton.disabled = plaza.estado === 'reservada';
                    offerButton.textContent = plaza.estado === 'ascenso_pendiente'
                        ? 'Retirar oferta'
                        : plaza.estado === 'reservada'
                            ? 'Interés reservado'
                            : 'Ofrecer para ascenso';
                }
                if (claimForm) {
                    claimForm.hidden = !canInteract
                        || isMyPlaza
                        || plaza.estado !== 'ascenso_pendiente';
                }
            });

            if (updatedLabel) {
                updatedLabel.textContent = `Estado actualizado: ${new Date(updatedAt).toLocaleTimeString('es-MX')}`;
            }
        } catch (error) {
            console.error('No fue posible actualizar el tablero de plazas.', error);
            if (updatedLabel) {
                updatedLabel.textContent = 'No se pudo actualizar el estado. Se intentará nuevamente.';
            }
        }
    };

    refreshPlazas();
    window.setInterval(refreshPlazas, 15000);
}

const themeToggle = document.querySelector('[data-theme-toggle]');

if (themeToggle) {
    const themeLabel = themeToggle.querySelector('[data-theme-label]');
    const sunIcon = themeToggle.querySelector('[data-theme-sun]');
    const moonIcon = themeToggle.querySelector('[data-theme-moon]');

    const setTheme = (theme) => {
        const isDark = theme === 'dark';
        document.documentElement.dataset.theme = theme;
        localStorage.setItem('itm-color-mode', theme);
        themeToggle.setAttribute('aria-pressed', String(isDark));
        themeLabel.textContent = isDark ? 'Modo claro' : 'Modo oscuro';
        sunIcon.classList.toggle('hidden', !isDark);
        moonIcon.classList.toggle('hidden', isDark);
    };

    setTheme(document.documentElement.dataset.theme === 'dark' ? 'dark' : 'light');

    themeToggle.addEventListener('click', () => {
        setTheme(document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark');
    });
}

const photoModal = document.querySelector('[data-photo-modal]');

if (photoModal) {
    const photoModalOpen = document.querySelector('[data-photo-modal-open]');
    const photoModalClose = photoModal.querySelector('[data-photo-modal-close]');
    const photoModalPanel = photoModal.querySelector('[data-photo-modal-panel]');
    const photoCurrent = photoModal.querySelector('[data-photo-current]');
    const photoEmpty = photoModal.querySelector('[data-photo-empty]');
    const photoEditor = photoModal.querySelector('[data-photo-editor]');
    const photoChooseButtons = photoModal.querySelectorAll('[data-photo-choose]');
    const photoInput = photoModal.querySelector('[data-photo-input]');
    const photoStage = photoModal.querySelector('[data-photo-crop-stage]');
    const photoPreviewCanvas = photoModal.querySelector('[data-photo-crop-preview]');
    const photoZoom = photoModal.querySelector('[data-photo-zoom]');
    const photoUploadForm = photoModal.querySelector('[data-photo-upload-form]');
    const photoSubmit = photoModal.querySelector('[data-photo-submit]');
    const photoCancelSelection = photoModal.querySelector('[data-photo-cancel-selection]');
    let photoObjectUrl = null;
    let photoSourceImage = null;
    let photoScale = 1;
    let photoOffsetX = 0;
    let photoOffsetY = 0;
    let photoBaseScale = 1;
    let photoImageWidth = 0;
    let photoImageHeight = 0;
    let photoDragging = false;
    let photoDragOrigin = null;
    let photoPreviousFocus = null;

    const setModalOpen = (isOpen) => {
        photoModal.classList.toggle('hidden', !isOpen);
        photoModal.classList.toggle('flex', isOpen);
        photoModal.setAttribute('aria-hidden', String(!isOpen));
        document.body.classList.toggle('overflow-hidden', isOpen);

        if (isOpen) {
            photoPreviousFocus = document.activeElement;
            photoModalClose?.focus();
        } else {
            photoPreviousFocus?.focus();
        }
    };

    const setCropImagePosition = () => {
        if (!photoPreviewCanvas || !photoStage || !photoSourceImage || !photoImageWidth || !photoImageHeight) {
            return;
        }

        photoScale = Number(photoZoom?.value ?? 1);
        const stageSize = photoStage.clientWidth;
        const scaledWidth = photoImageWidth * photoBaseScale * photoScale;
        const scaledHeight = photoImageHeight * photoBaseScale * photoScale;
        const maxOffsetX = Math.max(0, (scaledWidth - stageSize) / 2);
        const maxOffsetY = Math.max(0, (scaledHeight - stageSize) / 2);
        photoOffsetX = Math.max(-maxOffsetX, Math.min(maxOffsetX, photoOffsetX));
        photoOffsetY = Math.max(-maxOffsetY, Math.min(maxOffsetY, photoOffsetY));
        photoDrawCrop(photoPreviewCanvas, photoPreviewCanvas.width);
    };

    const photoGetCrop = () => {
        const sourceSize = Math.min(photoImageWidth, photoImageHeight) / photoScale;
        const sourceScale = photoBaseScale * photoScale;

        return {
            x: (photoImageWidth - sourceSize) / 2 - photoOffsetX / sourceScale,
            y: (photoImageHeight - sourceSize) / 2 - photoOffsetY / sourceScale,
            size: sourceSize,
        };
    };

    const photoDrawCrop = (canvas, outputSize) => {
        const context = canvas.getContext('2d');

        if (!context || !photoSourceImage) {
            throw new Error('No se pudo preparar la vista previa de la foto.');
        }

        const crop = photoGetCrop();
        canvas.width = outputSize;
        canvas.height = outputSize;
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, outputSize, outputSize);
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';
        context.drawImage(
            photoSourceImage,
            crop.x,
            crop.y,
            crop.size,
            crop.size,
            0,
            0,
            outputSize,
            outputSize,
        );
    };

    const showCurrentPhoto = () => {
        photoEditor?.classList.add('hidden');
        photoCurrent?.classList.remove('hidden');
        photoEmpty?.classList.remove('hidden');
        photoSubmit.disabled = true;
        photoSourceImage = null;
        photoImageWidth = 0;
        photoImageHeight = 0;
        if (photoObjectUrl) {
            URL.revokeObjectURL(photoObjectUrl);
            photoObjectUrl = null;
        }
        if (photoInput) {
            photoInput.value = '';
        }
    };

    const choosePhoto = () => {
        showCurrentPhoto();
        if (photoInput) {
            photoInput.value = '';
            photoInput.click();
        }
    };

    photoModalOpen?.addEventListener('click', () => setModalOpen(true));
    photoModalClose?.addEventListener('click', () => setModalOpen(false));
    photoModal.addEventListener('click', (event) => {
        if (event.target === photoModal) {
            setModalOpen(false);
        }
    });
    photoModalPanel?.addEventListener('click', (event) => event.stopPropagation());
    photoChooseButtons.forEach((button) => button.addEventListener('click', choosePhoto));
    photoCancelSelection?.addEventListener('click', showCurrentPhoto);
    photoZoom?.addEventListener('input', setCropImagePosition);

    photoInput?.addEventListener('change', () => {
        const file = photoInput.files?.[0];

        if (!file) {
            return;
        }

        if (photoObjectUrl) {
            URL.revokeObjectURL(photoObjectUrl);
        }

        photoObjectUrl = URL.createObjectURL(file);
        photoSourceImage = new Image();
        photoSourceImage.onload = () => {
            photoImageWidth = photoSourceImage.naturalWidth;
            photoImageHeight = photoSourceImage.naturalHeight;

            if (!photoImageWidth || !photoImageHeight || photoImageWidth > 12000 || photoImageHeight > 12000) {
                showCurrentPhoto();
                window.alert('La imagen no se puede procesar. Elige una imagen válida de hasta 12000 × 12000 px.');
                return;
            }

            photoOffsetX = 0;
            photoOffsetY = 0;
            photoZoom.value = '1';
            photoCurrent?.classList.add('hidden');
            photoEmpty?.classList.add('hidden');
            photoEditor?.classList.remove('hidden');
            const stageSize = photoStage.clientWidth;

            if (!stageSize) {
                showCurrentPhoto();
                window.alert('No se pudo preparar el área de recorte. Cierra la ventana e inténtalo de nuevo.');
                return;
            }

            photoBaseScale = stageSize / Math.min(photoImageWidth, photoImageHeight);
            photoSubmit.disabled = false;

            try {
                setCropImagePosition();
            } catch (error) {
                showCurrentPhoto();
                window.alert(error instanceof Error ? error.message : 'No se pudo mostrar la imagen seleccionada.');
            }
        };
        photoSourceImage.onerror = () => {
            showCurrentPhoto();
            window.alert('No se pudo abrir la imagen seleccionada. Prueba con otro archivo JPG, PNG o WebP.');
        };
        photoSourceImage.src = photoObjectUrl;
    });

    photoStage?.addEventListener('pointerdown', (event) => {
        if (photoEditor.classList.contains('hidden')) {
            return;
        }

        photoDragging = true;
        photoDragOrigin = {
            x: event.clientX,
            y: event.clientY,
            offsetX: photoOffsetX,
            offsetY: photoOffsetY,
        };
        photoStage.setPointerCapture(event.pointerId);
    });

    photoStage?.addEventListener('pointermove', (event) => {
        if (!photoDragging || !photoDragOrigin) {
            return;
        }

        photoOffsetX = photoDragOrigin.offsetX + event.clientX - photoDragOrigin.x;
        photoOffsetY = photoDragOrigin.offsetY + event.clientY - photoDragOrigin.y;
        setCropImagePosition();
    });

    photoStage?.addEventListener('pointerup', () => {
        photoDragging = false;
        photoDragOrigin = null;
    });
    photoStage?.addEventListener('pointercancel', () => {
        photoDragging = false;
        photoDragOrigin = null;
    });

    photoUploadForm?.addEventListener('submit', (event) => {
        if (!photoInput?.files?.[0] || !photoSourceImage || !photoImageWidth || !photoImageHeight) {
            event.preventDefault();
            return;
        }

        event.preventDefault();
        photoSubmit.disabled = true;
        try {
            const canvas = document.createElement('canvas');
            photoDrawCrop(canvas, 512);
            canvas.toBlob((blob) => {
                if (!blob || blob.size === 0) {
                    photoSubmit.disabled = false;
                    window.alert('No se pudo generar la foto recortada. Inténtalo de nuevo.');
                    return;
                }

                try {
                    const croppedPhoto = new File([blob], 'foto-perfil.jpg', { type: 'image/jpeg' });
                    const transfer = new DataTransfer();
                    transfer.items.add(croppedPhoto);
                    photoInput.files = transfer.files;
                    photoUploadForm.submit();
                } catch (error) {
                    photoSubmit.disabled = false;
                    window.alert(error instanceof Error ? error.message : 'No se pudo preparar la foto para subirla.');
                }
            }, 'image/jpeg', 0.9);
        } catch (error) {
            photoSubmit.disabled = false;
            window.alert(error instanceof Error ? error.message : 'No se pudo recortar la foto seleccionada.');
        }
    });

    document.addEventListener('keydown', (event) => {
        if (photoModal.getAttribute('aria-hidden') !== 'false') {
            return;
        }

        if (event.key === 'Escape') {
            setModalOpen(false);
            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusableElements = photoModal.querySelectorAll(
            'button:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])',
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (event.shiftKey && document.activeElement === firstElement) {
            event.preventDefault();
            lastElement?.focus();
        } else if (!event.shiftKey && document.activeElement === lastElement) {
            event.preventDefault();
            firstElement?.focus();
        }
    });

    if (photoModal.hasAttribute('data-photo-modal-autopen')) {
        setModalOpen(true);
        photoEditor?.classList.remove('hidden');
        photoCurrent?.classList.add('hidden');
        photoEmpty?.classList.add('hidden');
    }
}
