/*
 * Copyright Extend (c) 2024. All rights reserved.
 * See Extend-COPYING.txt for license details.
 */

document
  .querySelectorAll('#super-product-table tbody tr .price-box')
  .forEach((el, i) => {
    let id = el.getAttribute('data-price-box')
    if (document.querySelector('.' + id)) {
      el.closest('tr').after(document.querySelector('.' + id).closest('tr'))
    }
  })
