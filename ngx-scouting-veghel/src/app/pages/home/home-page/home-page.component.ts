import { Component, OnDestroy, OnInit } from '@angular/core';
import { SvBodyImageService } from '@services';

@Component({
  selector: 'sv-home-page',
  templateUrl: './home-page.component.html',
  styleUrls: ['./home-page.component.scss']
})
export class HomePageComponent implements OnInit, OnDestroy {
  private imageClass = 'home-page-image';

  constructor(private readonly bodyImage: SvBodyImageService) { }

  ngOnInit(): void {
    this.bodyImage.setBodyClass(this.imageClass);
  }

  ngOnDestroy(): void {
    this.bodyImage.removeClass(this.imageClass);
  }
}
