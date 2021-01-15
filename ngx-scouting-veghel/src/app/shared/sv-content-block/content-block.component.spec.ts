import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SvContentBlockComponent } from './content-block.component';

describe('SvContentBlockComponent', () => {
  let component: SvContentBlockComponent;
  let fixture: ComponentFixture<SvContentBlockComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SvContentBlockComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(SvContentBlockComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
