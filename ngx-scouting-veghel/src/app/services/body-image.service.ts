import { DOCUMENT } from '@angular/common';
import { Inject, Injectable } from '@angular/core';

@Injectable({ providedIn: 'root' })
export class SvBodyImageService {
  private latestImageClass = '';

  constructor(@Inject(DOCUMENT) private readonly document: Document) {}

  setBodyClass(className: string): void {
    this.document.body.classList.add(className);
    this.latestImageClass = className;
  }

  removeLatestClass(): void {
    this.document.body.classList.remove(this.latestImageClass);
  }

  removeClass(className: string): void {
    this.document.body.classList.remove(className);
    this.latestImageClass = '';
  }
}
